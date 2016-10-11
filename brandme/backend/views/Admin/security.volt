<h3>Seguridad</h3>

<?php if(isset($temporary_key)): ?>
<h4>Llave temporal:</h4>
<pre><?php echo $temporary_key ?></pre>
<?php endif; ?>
<form action="" method="post">
    <button class="btn red" type="submit" name="create-token">Crear Acceso temporal</button>
</form>
<br>
<span class="alert-warning admin-warning" style="padding:4px">
    Esta funcionalidad se genera una llave de acceso <i>global</i>, con una vigencia temporal de un solo uso y de 120 segundos para usar
</span>