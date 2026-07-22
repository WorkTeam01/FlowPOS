<?php
$URL = $URL ?? ($GLOBALS['URL'] ?? '');
$appName = $appName ?? ($GLOBALS['appName'] ?? 'FlowPOS');
$appVersion = $appVersion ?? ($GLOBALS['appVersion'] ?? '1.0.0');
?>
</main>
<!-- /.content-wrapper -->

<!-- Main Footer -->
<footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
        <div class="text-muted">
            <small>
                <i class="fas fa-tag"></i> Versión <?= htmlspecialchars($appVersion) ?>
            </small>
        </div>
    </div>
    <div class="footer-content">
        <strong>Copyright &copy; <?= date('Y'); ?>
            <a href="#" class="text-decoration-none"><?= $appName ?></a>
        </strong>
        - FlowPOS
    </div>
</footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- Bootstrap 4 -->
<script src="<?= $URL; ?>public/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?= $URL; ?>public/js/lib/adminlte/adminlte.min.js"></script>
<?php
// $skip_select2: opt-out para vistas que no usan Select2 (mismo condicional que header.php).
$cargar_select2 = !(isset($skip_select2) && $skip_select2 === true);
// $skip_datatables: opt-out para vistas sin tabla (evita cargar ~2.8MB de
// DataTables/jszip/pdfmake/vfs_fonts; mismo condicional que header.php).
$cargar_datatables = !(isset($skip_datatables) && $skip_datatables === true);
?>
<?php if ($cargar_select2): ?>
    <!-- Select2 -->
    <script src="<?= $URL; ?>public/js/plugins/select2/select2.min.js"></script>
<?php endif; ?>
<?php if ($cargar_datatables): ?>
    <!-- DataTables y sus extensiones -->
    <script src="<?= $URL; ?>public/js/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/responsive.bootstrap4.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/buttons.bootstrap4.min.js"></script>
    <!-- Utilidades para DataTables -->
    <script src="<?= $URL; ?>public/js/plugins/utils/jszip.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/utils/pdfmake.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/utils/vfs_fonts.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/buttons.html5.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/buttons.print.min.js"></script>
    <script src="<?= $URL; ?>public/js/plugins/datatables/buttons.colVis.min.js"></script>
<?php endif; ?>
<!-- Scripts principales de la aplicación -->
<script src="<?= $URL; ?>public/js/core/common-utils.js?v=<?= urlencode($appVersion) ?>"></script>
<!-- Moment.js para manejo de fechas -->
<script src="<?= $URL; ?>public/js/plugins/moment/moment.min.js"></script>
<!-- Scripts específicos por módulo -->
<?php if (isset($module_scripts) && is_array($module_scripts)): ?>
    <?php foreach ($module_scripts as $script): ?>
        <script src="<?= $URL; ?>public/js/modules/<?= $script; ?>.js?v=<?= urlencode($appVersion) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>

</html>