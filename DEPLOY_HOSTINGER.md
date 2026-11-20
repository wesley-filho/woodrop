# Publicar o WooDROP na Hostinger (com a integração do Mercado Livre)

Troque `SEU-DOMINIO` pelo domínio do site (ex.: `woodrop.com.br`) em todo o guia.

## 0. Antes de começar
- **Backup:** hPanel › Arquivos › Backups — gere um backup dos arquivos e do banco.
- **PHP:** hPanel › Avançado › Configuração do PHP — versão **8.1 ou superior** (recomendado 8.3), com as extensões
  `pdo_mysql`, `curl`, `fileinfo` e `sodium` ativas (normalmente já vêm ativas).
- **SSL:** hPanel › Segurança › SSL — o certificado precisa estar ativo (o Mercado Livre só aceita HTTPS).

## 1. Enviar os arquivos
1. Use o pacote `deploy/woodrop-hostinger.zip`. Ele **não** contém `.env`, `tests/`, `public/uploads/` nem arquivos do Mac,
   então não sobrescreve a configuração nem as imagens que já estão no servidor.
2. hPanel › Gerenciador de Arquivos › `public_html` › **Enviar** o zip › clique com o botão direito › **Extrair** (substituir arquivos).
3. Ative "Mostrar arquivos ocultos" e confirme que existe o **`.htaccess` na raiz** do `public_html`. Ele:
   - leva todas as páginas para a pasta `public/` (endereços limpos: `https://SEU-DOMINIO/login`);
   - **bloqueia o acesso** a `.env`, `app/`, `database/`, `scripts/`, `tests/` e outros arquivos internos.
4. Apague o zip do servidor depois de extrair.

## 2. Banco de dados (phpMyAdmin)
hPanel › Bancos de dados › phpMyAdmin › selecione o banco do WooDROP › **Importar**.

**Banco novo ou vazio:** importe só `database/woodrop_completo.sql` (estrutura + planos + categorias + usuários de teste).
Ele **apaga e recria** as tabelas do WooDROP — nunca use num banco que já tem dados reais.

**Banco que já está em uso** (atualização):
1. `database/migrations/001_mercadolivre.sql` — cria as tabelas da integração (rode uma vez só).
2. `database/migrations/002_corrige_acentos.sql` — **se** os planos ou categorias aparecerem com acentos quebrados
   (ex.: "BÃ¡sico"). É seguro rodar mais de uma vez.

## 3. Arquivo `.env` do servidor
Edite o `.env` que já está no `public_html` (Gerenciador de Arquivos) e acrescente/ajuste:

```
APP_ENV=production
APP_URL=https://SEU-DOMINIO
APP_KEY=COLE_AQUI_UMA_CHAVE_ALEATORIA
ML_APP_ID=
ML_CLIENT_SECRET=
ML_REDIRECT_URI=https://SEU-DOMINIO/integracoes/mercadolivre/retorno
ML_LISTING_TYPE=gold_special
```

- `APP_KEY`: gere no seu computador com `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Não compartilhe.
  Se trocar depois, os vendedores precisam reconectar o Mercado Livre.
- `ML_APP_ID` e `ML_CLIENT_SECRET`: vêm do passo 5.
- `APP_ENV=production` liga os cookies seguros (só HTTPS) e esconde mensagens de erro.

## 4. Tarefas agendadas (Cron)
hPanel › Avançado › Cron Jobs › tipo **Personalizado**. Confira o caminho da pasta no Gerenciador de Arquivos
(na Hostinger costuma ser `/home/USUARIO/domains/SEU-DOMINIO/public_html`).

| Frequência | Comando |
|---|---|
| `*/5 * * * *` | `/usr/bin/php /home/USUARIO/domains/SEU-DOMINIO/public_html/scripts/sync_mercadolivre.php` |
| `0 * * * *`   | `/usr/bin/php /home/USUARIO/domains/SEU-DOMINIO/public_html/scripts/check_order_deadlines.php` |
| `0 8 * * *`   | `/usr/bin/php /home/USUARIO/domains/SEU-DOMINIO/public_html/scripts/check_stock_integrity.php` |
| `5 0 * * *`   | `/usr/bin/php /home/USUARIO/domains/SEU-DOMINIO/public_html/scripts/expire_subscriptions.php` |

## 5. Aplicativo no Mercado Livre
Em [developers.mercadolivre.com.br/devcenter](https://developers.mercadolivre.com.br/devcenter), entre com a **sua conta**
e crie um aplicativo:

| Campo | Valor |
|---|---|
| URI de redirect | `https://SEU-DOMINIO/integracoes/mercadolivre/retorno` |
| PKCE | ativado |
| Permissões | leitura e escrita (anúncios e vendas) |
| URL de notificações | `https://SEU-DOMINIO/integracoes/mercadolivre/notificacoes` |
| Tópicos | `orders_v2` e `items` |

Copie o **App ID** e a **Secret Key** para `ML_APP_ID` e `ML_CLIENT_SECRET` no `.env` (passo 3).

## 6. Conferir a publicação
Abra no navegador:
- `https://SEU-DOMINIO/` e `https://SEU-DOMINIO/login` — devem abrir normalmente.
- `https://SEU-DOMINIO/.env`, `https://SEU-DOMINIO/app/config/config.php` e `https://SEU-DOMINIO/database/schema.sql`
  — devem mostrar **erro 403 ou 404** (nunca o conteúdo).
- Entre como vendedor › **Integrações** — deve aparecer o botão "Conectar conta do Mercado Livre".

## 7. Testar com lojas de teste
Siga a seção **"Lojas de teste (usuários de teste do ML)"** do `README.md`. Para criar os usuários de teste no servidor,
use o Terminal SSH da Hostinger (hPanel › Avançado › Acesso SSH):

```
cd ~/domains/SEU-DOMINIO/public_html && php scripts/ml_usuario_teste.php EMAIL_DO_VENDEDOR
```

## Segurança — antes de abrir para usuários reais
- Os usuários de teste do README (`admin@woodrop.com.br`, `fornecedor@teste.com`, `vendedor@teste.com`) têm senhas
  públicas. **Troque as senhas ou desative essas contas** no servidor de produção.
- Não envie `tests/` nem o `.env` local para o servidor.
