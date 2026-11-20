<?php
class Categoria { static function todas(): array { return qa('SELECT * FROM categorias ORDER BY nome'); } }
