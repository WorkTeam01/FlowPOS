<?php

if ((isset($_SESSION['mensaje'])) && (isset($_SESSION['icono']))) {
    $respuesta = $_SESSION['mensaje'];
    $icono = $_SESSION['icono']; ?>
    <div id="flash-mensaje" data-mensaje="<?= htmlspecialchars($respuesta, ENT_QUOTES, 'UTF-8'); ?>" data-icono="<?= htmlspecialchars($icono, ENT_QUOTES, 'UTF-8'); ?>" style="display:none"></div>
<?php
    unset($_SESSION['mensaje']);
    unset($_SESSION['icono']);
} ?>