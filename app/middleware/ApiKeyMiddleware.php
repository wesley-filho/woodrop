<?php
class ApiKeyMiddleware {
    /** Autentica a requisição da API por Bearer/X-API-Key (RN02), aplica rate limit e exige assinatura ativa (RN06). */
    static function autenticar(): array { return ApiAuthService::autenticar(); }
}
