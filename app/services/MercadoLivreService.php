<?php
/**
 * Integração WooDROP ↔ Mercado Livre.
 *  - Conexão da conta do vendedor por OAuth 2.0 + PKCE (tokens cifrados no banco, renovação automática).
 *  - Publicação de anúncios do WooDROP como itens no Mercado Livre (RF05).
 *  - Sincronização de preço, quantidade disponível e status sempre que estoque/anúncio mudam (RN01/RN05).
 *  - Importação das vendas pagas como pedidos para o fornecedor (RF07), por notificação (webhook) e por varredura (cron).
 */
class MercadoLivreService {
    const CANAL = MercadoLivreClient::CANAL;
    const TITULO_TESTE = 'Item de Teste – Por favor, NÃO OFERTAR!'; // exigido pelo ML para anúncios de usuários de teste
    const CATEGORIA_OUTROS = 'MLB3530'; // Mais Categorias > Outros: o ML pede que anúncios de teste usem esta categoria

    // ------------------------------------------------------------------ Conexão (OAuth 2.0)

    /** Monta a URL de autorização e guarda state + code_verifier na sessão. */
    static function urlAutorizacao(): string {
        [$verifier, $challenge] = MercadoLivreClient::pkce();
        $state = bin2hex(random_bytes(16));
        $_SESSION['ml_oauth'] = ['state' => $state, 'verifier' => $verifier, 'em' => time()];
        return MercadoLivreClient::authUrl() . '/authorization?' . http_build_query([
            'response_type' => 'code', 'client_id' => MercadoLivreClient::appId(), 'redirect_uri' => MercadoLivreClient::redirectUri(),
            'state' => $state, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256']);
    }

    /** Troca o code por tokens e grava a integração do vendedor. Retorna mensagem de erro ou null. */
    static function concluirConexao(int $uid, string $code, string $state): ?string {
        $o = $_SESSION['ml_oauth'] ?? null; unset($_SESSION['ml_oauth']);
        if (!$o || !hash_equals($o['state'], $state) || time() - $o['em'] > 900) return 'Autorização inválida ou expirada. Clique em "Conectar" novamente.';
        [$st, $t] = MercadoLivreClient::req('POST', '/oauth/token', null, null, [
            'grant_type' => 'authorization_code', 'client_id' => MercadoLivreClient::appId(), 'client_secret' => MercadoLivreClient::segredo(),
            'code' => $code, 'redirect_uri' => MercadoLivreClient::redirectUri(), 'code_verifier' => $o['verifier']]);
        if ($st !== 200 || empty($t['access_token']) || empty($t['refresh_token'])) return 'O Mercado Livre recusou a autorização: ' . MercadoLivreClient::erro($t, $st);
        [$st, $me] = MercadoLivreClient::req('GET', '/users/me', null, $t['access_token']);
        if ($st !== 200 || empty($me['id'])) return 'Não foi possível ler a conta do Mercado Livre: ' . MercadoLivreClient::erro($me, $st);
        $conta = (string)$me['id'];
        $outro = q1('SELECT usuario_id FROM integracoes WHERE canal=? AND conta_id=?', [self::CANAL, $conta]);
        if ($outro && (int)$outro['usuario_id'] !== $uid) return 'Esta conta do Mercado Livre já está conectada a outro usuário do WooDROP.';
        $antiga = self::integracao($uid);
        if ($antiga && $antiga['conta_id'] !== $conta) { // trocou de conta: os itens antigos pertencem à outra conta e não podem mais ser geridos
            q('DELETE x FROM anuncios_externos x JOIN anuncios a ON a.id=x.anuncio_id WHERE a.vendedor_id=? AND x.canal=?', [$uid, self::CANAL]);
        }
        $tags = (array)($me['tags'] ?? []);
        $v = [$conta, mb_substr((string)($me['nickname'] ?? ''), 0, 100), in_array('test_user', $tags, true) ? 1 : 0, in_array('user_product_seller', $tags, true) ? 1 : 0,
              cifrar($t['access_token']), cifrar($t['refresh_token']), self::expiraEm($t)];
        q('INSERT INTO integracoes (conta_id,conta_nome,conta_teste,user_products,access_token,refresh_token,expira_em,usuario_id,canal) VALUES (?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE conta_id=?, conta_nome=?, conta_teste=?, user_products=?, access_token=?, refresh_token=?, expira_em=?, status="ativa"',
          array_merge($v, [$uid, self::CANAL], $v));
        log_action('ml_conectado', "conta=$conta", $uid);
        return null;
    }

    static function integracao(int $uid): ?array { return q1('SELECT * FROM integracoes WHERE usuario_id=? AND canal=?', [$uid, self::CANAL]); }
    static function porConta(string $contaId): ?array { return q1('SELECT * FROM integracoes WHERE canal=? AND conta_id=?', [self::CANAL, $contaId]); }

    /** Revoga a permissão no Mercado Livre (melhor esforço) e apaga os tokens. Os itens publicados continuam no ML. */
    static function desconectar(int $uid): void {
        $integ = self::integracao($uid);
        if (!$integ) return;
        $tok = $integ['status'] === 'ativa' ? decifrar($integ['access_token']) : null;
        if ($tok) MercadoLivreClient::req('DELETE', '/users/' . rawurlencode($integ['conta_id']) . '/applications/' . rawurlencode(MercadoLivreClient::appId()), null, $tok);
        q('DELETE FROM integracoes WHERE id=?', [$integ['id']]);
        log_action('ml_desconectado', "conta={$integ['conta_id']}", $uid);
    }

    private static function expiraEm(array $t): string { return date('Y-m-d H:i:s', time() + max(60, (int)($t['expires_in'] ?? 21600)) - 60); }

    /**
     * Access token válido. Renova com o refresh_token quando faltam menos de 5 minutos (o refresh_token é de uso único,
     * por isso a renovação trava a linha). Se a autorização foi revogada, marca a integração para reconexão.
     */
    static function token(array &$integ): ?string {
        if ($integ['status'] !== 'ativa') return null;
        if (strtotime($integ['expira_em']) > time() + 300) return decifrar($integ['access_token']);
        $pdo = db(); $propria = !$pdo->inTransaction();
        if ($propria) $pdo->beginTransaction();
        try {
            $atual = q1('SELECT * FROM integracoes WHERE id=? FOR UPDATE', [$integ['id']]);
            if (!$atual) { if ($propria) $pdo->commit(); return null; }
            if (strtotime($atual['expira_em']) > time() + 300) { if ($propria) $pdo->commit(); $integ = $atual; return decifrar($atual['access_token']); }
            $refresh = decifrar($atual['refresh_token']);
            [$st, $t] = $refresh ? MercadoLivreClient::req('POST', '/oauth/token', null, null, ['grant_type' => 'refresh_token',
                'client_id' => MercadoLivreClient::appId(), 'client_secret' => MercadoLivreClient::segredo(), 'refresh_token' => $refresh]) : [400, ['error' => 'invalid_grant']];
            if ($st !== 200 || empty($t['access_token'])) {
                $revogado = in_array($t['error'] ?? '', ['invalid_grant', 'invalid_client', 'unauthorized_client', 'unauthorized_application'], true);
                if ($revogado) q('UPDATE integracoes SET status="reconectar" WHERE id=?', [$atual['id']]);
                if ($propria) $pdo->commit();
                if ($revogado) {
                    $integ['status'] = 'reconectar';
                    Notificacao::criar((int)$atual['usuario_id'], 'Reconecte o Mercado Livre', 'A autorização expirou ou foi revogada. Acesse Integrações e conecte a conta novamente.');
                    log_action('ml_token_revogado', MercadoLivreClient::erro($t, $st), (int)$atual['usuario_id']);
                }
                return null; // erro temporário (rede, 429): tenta de novo na próxima chamada
            }
            q('UPDATE integracoes SET access_token=?, refresh_token=?, expira_em=? WHERE id=?',
              [cifrar($t['access_token']), cifrar($t['refresh_token'] ?? $refresh), self::expiraEm($t), $atual['id']]);
            if ($propria) $pdo->commit();
            $integ = q1('SELECT * FROM integracoes WHERE id=?', [$atual['id']]);
            return $t['access_token'];
        } catch (Throwable $e) { if ($propria && $pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }

    /** Chamada autenticada em nome do vendedor. */
    static function api(array &$integ, string $metodo, string $path, ?array $json = null, array $headers = []): array {
        $tok = self::token($integ);
        if (!$tok) return [401, ['message' => 'Conta do Mercado Livre desconectada. Conecte novamente em Integrações.']];
        return MercadoLivreClient::req($metodo, $path, $json, $tok, null, $headers);
    }

    // ------------------------------------------------------------------ Anúncios

    /** Anúncios do vendedor com a situação de cada um no Mercado Livre (tela Integrações). */
    static function anunciosDoVendedor(int $vid): array {
        return qa('SELECT a.id, a.preco_venda, a.ativo, p.id produto_id, p.nome produto_nome, p.estoque_atual, p.ativo produto_ativo, u.nome fornecedor_nome,
                          x.item_id, x.status ml_status, x.permalink, x.erro, x.categoria_id, x.sincronizado_em
                   FROM anuncios a JOIN produtos p ON p.id=a.produto_id JOIN usuarios u ON u.id=p.fornecedor_id
                   LEFT JOIN anuncios_externos x ON x.anuncio_id=a.id AND x.canal=?
                   WHERE a.vendedor_id=? ORDER BY a.id DESC', [self::CANAL, $vid]);
    }

    static function anuncioDoVendedor(int $vid, int $aid): ?array {
        return q1('SELECT a.*, p.nome produto_nome, p.descricao, p.sku, p.estoque_atual, p.imagem_url, p.ativo produto_ativo, u.nome fornecedor_nome,
                          x.item_id, x.status ml_status, x.categoria_id
                   FROM anuncios a JOIN produtos p ON p.id=a.produto_id JOIN usuarios u ON u.id=p.fornecedor_id
                   LEFT JOIN anuncios_externos x ON x.anuncio_id=a.id AND x.canal=?
                   WHERE a.id=? AND a.vendedor_id=?', [self::CANAL, $aid, $vid]);
    }

    /** Quantidade que pode ser vendida no marketplace: estoque menos pedidos ainda não confirmados pelo fornecedor (reservados). */
    static function disponivel(int $produtoId, int $estoque): int {
        $res = (int)q1('SELECT COALESCE(SUM(quantidade),0) r FROM pedidos WHERE produto_id=? AND status="recebido"', [$produtoId])['r'];
        return max(0, $estoque - $res);
    }

    /** Publica o anúncio do WooDROP como item no Mercado Livre. $categoria opcional (ex.: MLB3530); sem ela: conta de teste usa "Outros", conta real usa o preditor do ML. */
    static function publicar(int $vid, int $aid, ?string $categoria = null): ?string {
        $integ = self::integracao($vid);
        if (!$integ) return 'Conecte sua conta do Mercado Livre primeiro.';
        if ($integ['status'] !== 'ativa') return 'A autorização do Mercado Livre expirou. Conecte a conta novamente.';
        $a = self::anuncioDoVendedor($vid, $aid);
        if (!$a) return 'Anúncio não encontrado.';
        if ($a['item_id']) return self::sincronizarAnuncio($aid);
        if (!$a['ativo'] || !$a['produto_ativo']) return 'Ative o anúncio (e o fornecedor, o produto) antes de publicar.';
        $qtd = self::disponivel((int)$a['produto_id'], (int)$a['estoque_atual']);
        if ($qtd < 1) return 'RN01: produto sem estoque disponível.';

        $categoria = $categoria ?: ($a['categoria_id'] ?: ($integ['conta_teste'] ? self::CATEGORIA_OUTROS : null));
        if (!$categoria) {
            [$st, $pred] = self::api($integ, 'GET', '/sites/' . MercadoLivreClient::SITE . '/domain_discovery/search?' . http_build_query(['limit' => 1, 'q' => $a['produto_nome']]));
            $categoria = ($st === 200 && !empty($pred[0]['category_id'])) ? (string)$pred[0]['category_id'] : null;
            if (!$categoria) return 'Não foi possível sugerir uma categoria. Informe o código da categoria do Mercado Livre (ex.: MLB3530 = Outros).';
        }
        if (!preg_match('/^MLB\d+$/', $categoria)) return 'Categoria inválida: use o código do Mercado Livre (ex.: MLB3530).';

        $titulo = $integ['conta_teste'] ? self::TITULO_TESTE : mb_substr($a['produto_nome'], 0, 60);
        $body = ['category_id' => $categoria, 'price' => round((float)$a['preco_venda'], 2), 'currency_id' => 'BRL', 'available_quantity' => $qtd,
                 'buying_mode' => 'buy_it_now', 'condition' => 'new', 'listing_type_id' => (string)env('ML_LISTING_TYPE', 'gold_special'),
                 'sale_terms' => [['id' => 'WARRANTY_TYPE', 'value_name' => 'Garantia do vendedor'], ['id' => 'WARRANTY_TIME', 'value_name' => '30 dias']],
                 'attributes' => self::atributos($integ, $categoria, $a)];
        if ($integ['user_products']) $body['family_name'] = $titulo; else $body['title'] = $titulo; // modelo User Products não aceita title
        if ($img = self::imagemPublica($a['imagem_url'])) $body['pictures'] = [['source' => $img]];

        [$st, $item] = self::api($integ, 'POST', '/items', $body);
        if (!in_array($st, [200, 201], true) || empty($item['id'])) {
            $err = MercadoLivreClient::erro($item, $st);
            self::gravarExterno($aid, null, $categoria, 'erro', null, $err);
            log_action('ml_publicar_falha', "anuncio=$aid $err", $vid);
            return 'O Mercado Livre recusou o anúncio: ' . $err;
        }
        $aviso = null;
        if (trim((string)$a['descricao']) !== '') {
            [$sd, $d] = self::api($integ, 'POST', '/items/' . rawurlencode($item['id']) . '/description', ['plain_text' => mb_substr(trim(strip_tags($a['descricao'])), 0, 50000)]);
            if (!in_array($sd, [200, 201], true)) $aviso = 'Descrição não enviada: ' . MercadoLivreClient::erro($d, $sd);
        }
        self::gravarExterno($aid, (string)$item['id'], $categoria, (string)($item['status'] ?? 'active'), $item['permalink'] ?? null, $aviso);
        log_action('ml_publicado', "anuncio=$aid item={$item['id']}", $vid);
        return null;
    }

    /** Ficha técnica mínima com o que o WooDROP conhece: marca (fornecedor), modelo/SKU, condição e motivo de não ter GTIN. */
    private static function atributos(array &$integ, string $cat, array $a): array {
        $attrs = [['id' => 'BRAND', 'value_name' => mb_substr($a['fornecedor_nome'], 0, 60)], ['id' => 'MODEL', 'value_name' => $a['sku']], ['id' => 'SELLER_SKU', 'value_name' => $a['sku']]];
        [$st, $lista] = self::api($integ, 'GET', '/categories/' . rawurlencode($cat) . '/attributes');
        if ($st !== 200 || !is_array($lista)) return $attrs;
        $por = []; foreach ($lista as $at) if (is_array($at) && isset($at['id'])) $por[$at['id']] = $at;
        $valor = function (string $id, string $padrao) use ($por): ?string { // procura o valor pelo nome (ex.: "Novo"), senão o primeiro
            $vals = $por[$id]['values'] ?? [];
            foreach ($vals as $v) if (isset($v['name']) && mb_stripos($v['name'], $padrao) !== false) return (string)$v['id'];
            return isset($vals[0]['id']) ? (string)$vals[0]['id'] : null;
        };
        if (isset($por['ITEM_CONDITION']) && ($id = $valor('ITEM_CONDITION', 'Novo'))) $attrs[] = ['id' => 'ITEM_CONDITION', 'value_id' => $id];
        $tags = (array)($por['GTIN']['tags'] ?? []);
        if ((!empty($tags['required']) || !empty($tags['conditional_required'])) && isset($por['EMPTY_GTIN_REASON']) && ($id = $valor('EMPTY_GTIN_REASON', 'não tem')))
            $attrs[] = ['id' => 'EMPTY_GTIN_REASON', 'value_id' => $id];
        return $attrs;
    }

    /** O ML baixa a imagem pela internet: só envia se o APP_URL for público. */
    private static function imagemPublica(?string $rel): ?string {
        if (!$rel) return null;
        $u = APP_URL . '/' . ltrim($rel, '/');
        $host = strtolower((string)parse_url($u, PHP_URL_HOST));
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true) || str_ends_with($host, '.localhost') ? null : $u;
    }

    private static function gravarExterno(int $aid, ?string $itemId, ?string $cat, string $status, ?string $link, ?string $erro): void {
        q('INSERT INTO anuncios_externos (anuncio_id,canal,item_id,categoria_id,status,permalink,erro,sincronizado_em) VALUES (?,?,?,?,?,?,?,NOW())
           ON DUPLICATE KEY UPDATE item_id=COALESCE(?,item_id), categoria_id=COALESCE(?,categoria_id), status=?, permalink=COALESCE(?,permalink), erro=?, sincronizado_em=NOW()',
          [$aid, self::CANAL, $itemId, $cat, $status, $link, $erro, $itemId, $cat, $status, $link, $erro]);
    }

    /** Envia preço, quantidade disponível e status do anúncio para o item no ML. Retorna erro ou null. */
    static function sincronizarAnuncio(int $aid): ?string {
        $a = q1('SELECT a.*, p.estoque_atual, p.ativo produto_ativo, x.item_id, x.status ml_status
                 FROM anuncios a JOIN produtos p ON p.id=a.produto_id JOIN anuncios_externos x ON x.anuncio_id=a.id AND x.canal=? WHERE a.id=?', [self::CANAL, $aid]);
        if (!$a || !$a['item_id']) return 'Anúncio ainda não publicado no Mercado Livre.';
        if ($a['ml_status'] === 'closed') return 'Item encerrado no Mercado Livre.';
        $integ = self::integracao((int)$a['vendedor_id']);
        if (!$integ) return 'Conta do Mercado Livre desconectada.';
        $qtd = self::disponivel((int)$a['produto_id'], (int)$a['estoque_atual']);
        $ativo = $a['ativo'] && $a['produto_ativo'];
        $body = $ativo ? ['price' => round((float)$a['preco_venda'], 2), 'available_quantity' => $qtd] : ['status' => 'paused'];
        if ($ativo && $qtd > 0 && $a['ml_status'] === 'paused') $body['status'] = 'active';
        [$st, $item] = self::api($integ, 'PUT', '/items/' . rawurlencode($a['item_id']), $body);
        if ($st !== 200) {
            $err = MercadoLivreClient::erro($item, $st);
            q('UPDATE anuncios_externos SET erro=?, sincronizado_em=NOW() WHERE anuncio_id=? AND canal=?', [$err, $aid, self::CANAL]);
            return 'Falha ao sincronizar com o Mercado Livre: ' . $err;
        }
        q('UPDATE anuncios_externos SET status=?, permalink=COALESCE(?,permalink), erro=NULL, sincronizado_em=NOW() WHERE anuncio_id=? AND canal=?',
          [(string)($item['status'] ?? $a['ml_status']), $item['permalink'] ?? null, $aid, self::CANAL]);
        return null;
    }

    /** Chamado sempre que estoque, produto, anúncio ou pedidos de um produto mudam. Nunca interrompe a operação principal. */
    static function sincronizarProduto(int $produtoId): void {
        try {
            foreach (qa('SELECT x.anuncio_id FROM anuncios_externos x JOIN anuncios a ON a.id=x.anuncio_id WHERE a.produto_id=? AND x.canal=? AND x.item_id IS NOT NULL', [$produtoId, self::CANAL]) as $r)
                self::sincronizarAnuncio((int)$r['anuncio_id']);
        } catch (Throwable $e) { log_action('ml_erro', 'sincronizar produto=' . $produtoId . ': ' . $e->getMessage()); }
    }

    /** Produto excluído: encerra (closed) os itens no Mercado Livre. */
    static function encerrarProduto(int $produtoId): void {
        try {
            foreach (qa('SELECT a.vendedor_id, x.item_id FROM anuncios_externos x JOIN anuncios a ON a.id=x.anuncio_id WHERE a.produto_id=? AND x.canal=? AND x.item_id IS NOT NULL', [$produtoId, self::CANAL]) as $r) {
                $integ = self::integracao((int)$r['vendedor_id']);
                if ($integ) self::api($integ, 'PUT', '/items/' . rawurlencode($r['item_id']), ['status' => 'closed']);
            }
        } catch (Throwable $e) { log_action('ml_erro', 'encerrar produto=' . $produtoId . ': ' . $e->getMessage()); }
    }

    // ------------------------------------------------------------------ Vendas -> pedidos

    /** Importa uma venda (order) do ML como pedido(s) do WooDROP. Idempotente. Retorna quantos pedidos foram criados. */
    static function importarPedido(array &$integ, string $orderId): int {
        [$st, $o] = self::api($integ, 'GET', '/orders/' . rawurlencode($orderId));
        if ($st !== 200 || empty($o['id'])) throw new RuntimeException("GET /orders/$orderId: " . MercadoLivreClient::erro($o, $st));
        if ((string)($o['seller']['id'] ?? '') !== (string)$integ['conta_id']) return 0; // venda de outra conta
        $status = (string)($o['status'] ?? '');
        if ($status === 'cancelled') { self::cancelarImportado((string)$o['id']); return 0; }
        if ($status !== 'paid') return 0; // só vendas pagas viram pedido para o fornecedor
        $vid = (int)$integ['usuario_id']; $cli = null; $n = 0;
        foreach ((array)($o['order_items'] ?? []) as $oi) {
            $itemId = (string)($oi['item']['id'] ?? '');
            $a = q1('SELECT a.produto_id FROM anuncios_externos x JOIN anuncios a ON a.id=x.anuncio_id WHERE x.canal=? AND x.item_id=? AND a.vendedor_id=?', [self::CANAL, $itemId, $vid]);
            if (!$a) continue; // item não publicado pelo WooDROP
            if (q1('SELECT id FROM pedidos WHERE canal=? AND pedido_externo_id=? AND produto_id=?', [self::CANAL, (string)$o['id'], $a['produto_id']])) continue;
            $cli ??= self::cliente($integ, $o);
            try {
                [$pid, $err] = Pedido::criar(['id' => $vid], $cli + ['produto_id' => $a['produto_id'], 'quantidade' => (int)($oi['quantity'] ?? 0),
                    'observacoes' => "Venda Mercado Livre #{$o['id']} (item $itemId)."], ['canal' => self::CANAL, 'id' => (string)$o['id']]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') continue; // importado em paralelo por outro processo
                throw $e;
            }
            if ($err) {
                $det = "order={$o['id']} item=$itemId";
                if (!q1('SELECT id FROM logs WHERE acao="ml_pedido_falha" AND detalhes LIKE ?', [$det . '%'])) {
                    log_action('ml_pedido_falha', "$det $err", $vid);
                    Notificacao::criar($vid, "Venda ML #{$o['id']} não virou pedido", "$err Resolva a venda no Mercado Livre (ex.: cancelar).");
                }
                continue;
            }
            log_action('ml_pedido_importado', "order={$o['id']} pedido=$pid", $vid);
            $n++;
        }
        return $n;
    }

    /** Dados de entrega a partir do envio (GET /shipments com x-format-new). Sem endereço, usa a etiqueta do Mercado Envios. */
    private static function cliente(array &$integ, array $o): array {
        $b = (array)($o['buyer'] ?? []);
        $c = ['cliente_nome' => trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')) ?: (string)($b['nickname'] ?? 'Comprador Mercado Livre'),
              'cliente_endereco' => 'Ver etiqueta do Mercado Envios', 'cliente_cidade' => '-', 'cliente_estado' => '', 'cliente_cep' => '-', 'cliente_telefone' => null];
        if ($sid = $o['shipping']['id'] ?? null) {
            [$st, $s] = self::api($integ, 'GET', '/shipments/' . rawurlencode((string)$sid), null, ['x-format-new: true']);
            $d = (array)($s['destination'] ?? []); $ad = (array)($d['shipping_address'] ?? []);
            if ($st === 200 && $ad) {
                $linha = trim((string)($ad['address_line'] ?? '')) ?: trim(($ad['street_name'] ?? '') . ', ' . ($ad['street_number'] ?? ''), ' ,');
                if (!empty($ad['neighborhood']['name'])) $linha .= ' - ' . $ad['neighborhood']['name'];
                $uf = preg_match('/([A-Z]{2})$/', (string)($ad['state']['id'] ?? ''), $m) ? $m[1] : null;
                $c = array_merge($c, array_filter(['cliente_nome' => $d['receiver_name'] ?? null, 'cliente_endereco' => $linha ?: null,
                    'cliente_cidade' => $ad['city']['name'] ?? null, 'cliente_estado' => $uf, 'cliente_cep' => $ad['zip_code'] ?? null, 'cliente_telefone' => $d['receiver_phone'] ?? null]));
            }
        }
        foreach (['cliente_nome' => 150, 'cliente_endereco' => 255, 'cliente_cidade' => 100, 'cliente_estado' => 2, 'cliente_cep' => 10, 'cliente_telefone' => 20] as $k => $max)
            if ($c[$k] !== null) $c[$k] = mb_substr((string)$c[$k], 0, $max);
        return $c;
    }

    /** Venda cancelada no ML: cancela o pedido no WooDROP se ainda não foi enviado (StockService devolve o estoque, se já baixado). */
    private static function cancelarImportado(string $orderId): void {
        foreach (qa('SELECT id, fornecedor_id FROM pedidos WHERE canal=? AND pedido_externo_id=? AND status IN ("recebido","em_separacao")', [self::CANAL, $orderId]) as $p)
            StockService::atualizarStatus((int)$p['id'], (int)$p['fornecedor_id'], 'cancelado');
    }

    /** Varredura de segurança: importa vendas pagas recentes (cobre notificações perdidas e ambientes sem URL pública). */
    static function importarRecentes(array &$integ, int $dias = 3): int {
        [$st, $r] = self::api($integ, 'GET', '/orders/search?' . http_build_query(['seller' => $integ['conta_id'], 'order.status' => 'paid', 'sort' => 'date_desc',
            'order.date_created.from' => date('Y-m-d\TH:i:s.000P', time() - $dias * 86400), 'limit' => 50]));
        if ($st !== 200) throw new RuntimeException('GET /orders/search: ' . MercadoLivreClient::erro($r, $st));
        $n = 0;
        foreach ((array)($r['results'] ?? []) as $o) {
            if (empty($o['id']) || q1('SELECT id FROM pedidos WHERE canal=? AND pedido_externo_id=? LIMIT 1', [self::CANAL, (string)$o['id']])) continue;
            $n += self::importarPedido($integ, (string)$o['id']);
        }
        return $n;
    }

    // ------------------------------------------------------------------ Notificações (webhook)

    /** Valida e enfileira uma notificação. O ML exige HTTP 200 em até 500 ms, então o processamento é feito depois. */
    static function enfileirar(array $n): bool {
        $topico = (string)($n['topic'] ?? ''); $rec = (string)($n['resource'] ?? ''); $conta = (string)($n['user_id'] ?? '');
        if ((string)($n['application_id'] ?? '') !== MercadoLivreClient::appId() || MercadoLivreClient::appId() === '') return false;
        $valida = ($topico === 'orders_v2' && preg_match('#^/orders/\d+$#', $rec)) || ($topico === 'items' && preg_match('#^/items/MLB\d+$#', $rec));
        if (!$valida || !preg_match('/^\d+$/', $conta) || !self::porConta($conta)) return false;
        q('INSERT INTO marketplace_notificacoes (canal,topico,recurso,conta_id) VALUES (?,?,?,?)', [self::CANAL, $topico, $rec, $conta]);
        return true;
    }

    /** Processa a fila de notificações. Os dados são sempre lidos da API do ML com o token do vendedor (a notificação só diz "o que mudou"). */
    static function processarFila(int $limite = 50): int {
        $n = 0;
        foreach (qa('SELECT * FROM marketplace_notificacoes WHERE canal=? AND processado_em IS NULL AND tentativas < 5 ORDER BY id LIMIT ' . (int)$limite, [self::CANAL]) as $f) {
            try {
                $integ = self::porConta($f['conta_id']);
                if ($integ && $integ['status'] === 'ativa') {
                    if ($f['topico'] === 'orders_v2') self::importarPedido($integ, basename($f['recurso']));
                    elseif ($f['topico'] === 'items') self::atualizarStatusItem($integ, basename($f['recurso']));
                }
                q('UPDATE marketplace_notificacoes SET processado_em=NOW(), tentativas=tentativas+1, erro=NULL WHERE id=?', [$f['id']]); $n++;
            } catch (Throwable $e) {
                q('UPDATE marketplace_notificacoes SET tentativas=tentativas+1, erro=? WHERE id=?', [mb_substr($e->getMessage(), 0, 1000), $f['id']]);
            }
        }
        q('DELETE FROM marketplace_notificacoes WHERE processado_em < (NOW() - INTERVAL 30 DAY)');
        return $n;
    }

    private static function atualizarStatusItem(array &$integ, string $itemId): void {
        $x = q1('SELECT x.id FROM anuncios_externos x JOIN anuncios a ON a.id=x.anuncio_id WHERE x.canal=? AND x.item_id=? AND a.vendedor_id=?', [self::CANAL, $itemId, $integ['usuario_id']]);
        if (!$x) return;
        [$st, $item] = self::api($integ, 'GET', '/items/' . rawurlencode($itemId));
        if ($st !== 200) throw new RuntimeException("GET /items/$itemId: " . MercadoLivreClient::erro($item, $st));
        q('UPDATE anuncios_externos SET status=?, permalink=COALESCE(?,permalink) WHERE id=?', [(string)($item['status'] ?? ''), $item['permalink'] ?? null, $x['id']]);
    }

    // ------------------------------------------------------------------ Testes

    /** Cria um usuário de teste do ML (máx. 10 por conta). Use um vendedor e um comprador de teste. */
    static function criarUsuarioTeste(array &$integ): array {
        [$st, $r] = self::api($integ, 'POST', '/users/test_user', ['site_id' => MercadoLivreClient::SITE]);
        if (!in_array($st, [200, 201], true) || empty($r['id'])) throw new RuntimeException(MercadoLivreClient::erro($r, $st));
        return $r;
    }
}
