# WooDROP

Plataforma de dropshipping regional que conecta **fornecedores do Caparaó (ES)** a **vendedores on-line**, conforme o TCC
*"WooDROP: Conexão entre Empresas Locais e o Mercado On-line"* (W. G. Assis Filho, IFES – Campus Alegre, 2026).

Stack: PHP 8+ (MVC leve, PDO), MySQL 8 (InnoDB), Bootstrap 5, Chart.js, API REST (JSON) com OpenAPI/Swagger.

> **Hospedagem Hostinger:** siga o passo a passo em [`DEPLOY_HOSTINGER.md`](DEPLOY_HOSTINGER.md). O `.htaccess` da raiz
> encaminha o site para `public/` e bloqueia o acesso a `.env`, `app/`, `database/` e demais arquivos internos.

## Instalação (XAMPP / LAMP)
1. Requisitos: PHP ≥ 8.0 com extensões `pdo_mysql`, `fileinfo`, `curl`; MySQL 8; Apache com `mod_rewrite` e `AllowOverride All`.
2. Copie a pasta `woodrop/` para `htdocs/` (XAMPP) ou `/var/www/`. O *document root* deve ser `woodrop/public`
   (ou acesse `http://localhost/woodrop/public`).
3. `cp .env.example .env` e ajuste:
   ```
   APP_ENV=development        # use "production" em produção (cookies Secure, erros ocultos)
   APP_URL=http://localhost/woodrop/public
   DB_HOST=127.0.0.1  DB_NAME=woodrop  DB_USER=root  DB_PASS=
   ```
4. Importe o banco — **um único script** com estrutura, dados iniciais e usuários de teste:
   ```bash
   mysql -u root -p -e "CREATE DATABASE woodrop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p woodrop < database/woodrop_completo.sql
   ```
   (No phpMyAdmin: selecione o banco › Importar › `database/woodrop_completo.sql`. O script **apaga e recria** as tabelas.)
   Alternativa em partes: `schema.sql` + `seed.sql` + `php scripts/seed_users.php`. Depois de mudar `schema.sql` ou
   `seed.sql`, gere o script único de novo com `php scripts/gerar_sql_completo.php`.
5. Permissão de escrita em `public/uploads/`. Sem Apache: `php -S localhost:8000 -t public` e use `APP_URL=http://localhost:8000`.

### Usuários de teste
| Perfil | E-mail | Senha |
|---|---|---|
| Admin | admin@woodrop.com.br | Admin@123 (**altere!**) |
| Fornecedor | fornecedor@teste.com | Teste@123 |
| Vendedor | vendedor@teste.com | Teste@123 |

## Scripts cron
```
0 8 * * *  php /caminho/woodrop/scripts/check_stock_integrity.php   # RN07/RN08: questionários e alertas de estoque
0 * * * *  php /caminho/woodrop/scripts/check_order_deadlines.php   # RN03: 24h úteis para confirmar pedido (penalidade)
5 0 * * *  php /caminho/woodrop/scripts/expire_subscriptions.php    # RN06: expira assinaturas vencidas
*/5 * * * * php /caminho/woodrop/scripts/sync_mercadolivre.php     # Mercado Livre: fila de notificações, vendas recentes, reenvio de falhas
```

## API
Documentação Swagger: `/api/docs`. Obtenha uma chave pelo painel (menu **API Keys**) ou via login:
```bash
curl -X POST $APP_URL/api/v1/auth/login -H "Content-Type: application/json" -d '{"email":"vendedor@teste.com","senha":"Teste@123"}'
curl $APP_URL/api/v1/products -H "Authorization: Bearer SUA_CHAVE"
curl $APP_URL/api/v1/stock/1  -H "X-API-Key: SUA_CHAVE"
curl -X POST $APP_URL/api/v1/orders -H "Authorization: Bearer SUA_CHAVE" -H "Content-Type: application/json" \
 -d '{"produto_id":1,"quantidade":1,"cliente_nome":"Maria","cliente_endereco":"Rua A, 1","cliente_cidade":"Vitória","cliente_estado":"ES","cliente_cep":"29000-000"}'
curl -X PATCH $APP_URL/api/v1/orders/1/status -H "Authorization: Bearer CHAVE_DO_FORNECEDOR" -H "Content-Type: application/json" -d '{"status":"em_separacao"}'
```
Testes automatizados: `php tests/api_test.php http://localhost/woodrop/public`

## Integração com o Mercado Livre
O vendedor conecta a conta dele no menu **Integrações** e publica seus anúncios no Mercado Livre (ML). A partir daí:
- **Vendas pagas** no ML viram **pedidos** para o fornecedor (com endereço de entrega do envio). Venda cancelada no ML cancela o pedido.
- **Estoque, preço e status** vão para o ML sozinhos: quando o fornecedor confirma/cancela pedido ou ajusta o estoque, quando o
  produto ou o anúncio é ativado/desativado e quando o vendedor muda o preço. A quantidade enviada é o estoque menos os pedidos ainda
  não confirmados (reservados), para não vender o que já está prometido.

Código: `app/services/MercadoLivreClient.php` (HTTP), `app/services/MercadoLivreService.php` (regras), `app/controllers/IntegrationController.php`,
`app/views/integrations/index.php`, `scripts/sync_mercadolivre.php`. Banco: `integracoes`, `anuncios_externos`, `marketplace_notificacoes` e as
colunas `pedidos.canal`/`pedidos.pedido_externo_id`.

### 1. Configuração
1. Banco já existente: `mysql -u root -p woodrop < database/migrations/001_mercadolivre.sql` (instalação nova: já vem no `schema.sql`).
2. Crie um aplicativo no [DevCenter do Mercado Livre](https://developers.mercadolivre.com.br/devcenter):
   - **URI de redirect:** `https://SEU-DOMINIO/integracoes/mercadolivre/retorno` (HTTPS público, igual ao do `.env`).
   - **Permissões:** leitura e escrita de anúncios e vendas; ative o **PKCE** (o WooDROP sempre envia).
   - **Notificações:** URL `https://SEU-DOMINIO/integracoes/mercadolivre/notificacoes`, tópicos `orders_v2` e `items`.
3. No `.env`: `APP_KEY`, `ML_APP_ID`, `ML_CLIENT_SECRET`, `ML_REDIRECT_URI` (modelo em `.env.example`).
4. Agende o `scripts/sync_mercadolivre.php` no cron (a cada 5 min).

**Testando no computador (sem HTTPS):** o ML só volta para endereços HTTPS públicos. Use o domínio da hospedagem ou um túnel:
`bash scripts/tunel_mercadolivre.sh` (requer `brew install cloudflared`) sobe um túnel Cloudflare `https://….trycloudflare.com`, o WooDROP em
`127.0.0.1:8080` já com `APP_URL`/`ML_REDIRECT_URI` do túnel e a sincronização a cada 60 s, e mostra as URLs para cadastrar no app do ML.
O endereço muda a cada execução (atualize o app do ML) e o site fica público enquanto o script roda (Ctrl+C encerra).
O túnel da Cloudflare precisa da porta **7844** (UDP ou TCP) liberada para saída; em redes que a bloqueiam ele não conecta
(o log mostra `QUIC connection failed` e `HTTP/2 connection is blocked`). Nesse caso use outra rede, o domínio da hospedagem ou o
ngrok (porta 443, conta gratuita, domínio fixo).
Sem URL pública as notificações não chegam: use o botão **Importar vendas agora** ou rode `php scripts/sync_mercadolivre.php`.

### 2. Lojas de teste (usuários de teste do ML)
O ML não tem sandbox: os testes são feitos no site real, com **usuários de teste** (até 10 por conta, apagados após 60 dias sem uso,
só negociam entre si, sem cobrança).
1. Conecte a **sua conta real** em Integrações (só para criar os usuários de teste).
2. Crie um vendedor e um comprador de teste (rode 2 vezes e **guarde as senhas** — o ML não mostra de novo):
   `php scripts/ml_usuario_teste.php vendedor@teste.com`
3. Saia da sua conta no mercadolivre.com.br (ou use janela anônima), clique em **Reconectar** e entre com o **vendedor de teste**.
   O código de verificação por e-mail é o final do ID do usuário de teste.
4. Publique um anúncio. Para conta de teste, o WooDROP usa automaticamente o título exigido pelo ML
   ("Item de Teste – Por favor, NÃO OFERTAR!"). Sem categoria informada, conta de teste usa a categoria **Outros** (`MLB3530`, como o ML pede) e conta real usa o preditor de categorias do ML.
5. Com o **comprador de teste**, compre o item usando um cartão de teste do Mercado Pago (titular `APRO APRO` = pagamento aprovado).
6. A venda chega como pedido para o fornecedor (notificação ou **Importar vendas agora**).

Contas no modelo **User Products** do ML publicam com `family_name` em vez de `title` (o WooDROP detecta pela tag `user_product_seller`).
Para testar esse modelo com usuários de teste, o ML pede a ativação por um formulário (página "User Products" da documentação).

### 3. Testes automatizados (ML simulado, sem internet)
`php tests/mercadolivre_test.php` sobe o WooDROP em `:8101` e um **Mercado Livre simulado** (`tests/mock_mercadolivre.php`) em `:8102`
e testa o fluxo inteiro: OAuth + PKCE, publicação, webhook, importação, confirmação, estoque, preço, pausa, renovação de token,
cancelamento e revogação. Use `MOCK_ML_TAGS=user_product_seller php tests/mercadolivre_test.php` para o modelo User Products.

### Limites desta versão
- Ficha técnica mínima: marca (= nome do fornecedor), modelo/SKU, condição "novo" e motivo de não ter GTIN. Se a categoria exigir outros
  atributos, a mensagem do ML aparece na tela; escolha outra categoria ou complete o anúncio no ML.
- Imagens só são enviadas quando o `APP_URL` é público (o ML baixa a imagem pela internet). Sem variações (cor/tamanho).
- Frete e etiqueta seguem o Mercado Envios da conta do vendedor; o repasse da etiqueta ao fornecedor fica fora do sistema.

## Regras de negócio → onde estão
| Regra | Implementação |
|---|---|
| RN01 | `Anuncio::salvar`, `Pedido::criar`, `Produto::catalogo` |
| RN02 | `ApiKey`, `ApiAuthService` (chave guardada como SHA-256) |
| RN03 | `scripts/check_order_deadlines.php` |
| RN04 | `Anuncio::salvar` |
| RN05 | `StockService::atualizarStatus` (baixa na confirmação, em transação) |
| RN06 | `AuthMiddleware::exigir`, `ApiAuthService`, `expire_subscriptions.php` |
| RN07 | `check_stock_integrity.php`, `StockService::verificar` |
| RN08 | `StockService::atualizarStatus` (2ª etapa) |
| RN09 | Aviso no rodapé, pedidos, cadastro e API |

## Decisões e limites desta versão
- **Planos** (`planos_assinatura.preco_base` = valor do período sem desconto): Básico R$ 49,90; Plus R$ 299,40 − 10% = R$ 269,46; Premium R$ 598,80 − 20% = R$ 479,04. O TCC não define valores em reais — são premissas ajustáveis em `database/seed.sql`.
- Pagamento de assinatura é **simulado** (sem gateway). Plus/Premium liberam relatórios e SKUs ilimitados; Básico limita a 100 SKUs; limite de API/min: 60/300/1000.
- `PATCH /orders/{id}/status` via API Key do fornecedor é uma extensão (o TCC prevê a atualização pela interface web).
- Colunas/tabelas extras ao modelo lógico: `usuarios.penalidades`, `produtos.verificado_em`, `pedidos.confirmado_em/penalizado`, `logs`, `rate_limits`.
- A interface é renderizada no servidor; AJAX/fetch é usado em notificações e atualização de estoque.
- **O código não foi executado em PHP/MySQL no ambiente de geração**: faça o primeiro teste local (`tests/api_test.php`) e reporte erros.

## Segurança
Argon2id, PDO com prepared statements, CSRF em todos os POSTs, `htmlspecialchars` na saída, sessão HttpOnly/SameSite=Strict (+Secure em HTTPS/produção), `session_regenerate_id`, rate limit (login e API), upload validado por MIME real, logs de ações críticas, redirecionamento HTTPS no `.htaccess`.
