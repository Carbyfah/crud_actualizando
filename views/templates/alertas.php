<?php
    foreach ($alertas as $key => $mensajes) :
        foreach ($mensajes as $mensaje) :
?>
    <div class="alert col-12 alert-<?= $key ?> alert-dismissible fade show">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php   
        endforeach;
    endforeach;
?>
