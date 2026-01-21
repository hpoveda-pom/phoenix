<?php if(!$cod_error){ ?>
<div class="card g-3 pt-2 w-auto">
  <form action="" method="get" name="report" id="filter" onsubmit="disableButtons(this)">
    <input type="hidden" name="Id" value="<?php echo $Id?>">
    <!-- field filters -->
    <div class="row g-0">
      <div class="col-12 col-md-auto p-1">
        <select class="form-select" id="inputState" name="Filter[field]">
          <option selected="selected" value="">Filtrar</option>
          <?php if (isset($array_headers['headers']) && is_array($array_headers['headers'])): ?>
          <?php foreach ($array_headers['headers'] as $key_headers => $row_headers) { ?>
            <option value="<?php echo $row_headers; ?>"><?php echo getFieldAlias($row_headers); ?></option>
          <?php } ?>
          <?php endif; ?>
        </select>
      </div>
      <!-- operators filters -->
      <div class="col-12 col-md-auto p-1">
        <select class="form-select" id="operator" name="Filter[operator]">
          <option value="=">Igual a</option>
          <option value="like">Que contenga</option>
          <option value="not like">Que no contenga</option>
          <option value="<>">Diferente a</option>
          <option value="BETWEEN">Entre</option>
          <option value=">">Mayor a</option>
          <option value="<">Menor que</option>
          <option value="is null">Nulo / Vacio</option>
          <option value="in">En lista</option>
          <option value="not in">No en lista</option>
          <option value="REGEXP_LIKE">Regexp</option>
          <option value="NOT REGEXP_LIKE">No Regexp</option>
          </select>
        </div>
        <!-- search by keyword -->
        <div class="col-12 col-md-auto p-1">
          <input class="form-control" id="keyword" type="text" name="Filter[keyword]" placeholder="Palabra / Valor clave">
        </div>
        <!-- grouping by fields -->
        <div class="col-12 col-md-auto p-1">
          <select class="form-select" id="GroupBy" name="GroupBy[field]">
            <option selected="selected" value="">Agrupar</option>
            <?php if (isset($array_headers['headers']) && is_array($array_headers['headers'])): ?>
            <?php foreach ($array_headers['headers'] as $key_headers => $row_headers) { ?>
              <option value="<?php echo $row_headers; ?>"><?php echo getFieldAlias($row_headers); ?></option>
            <?php } ?>
            <?php endif; ?>
          </select>
        </div>
        <!-- sum fields - solo visible cuando hay GroupBy -->
        <?php if (isset($groupby_results) && !empty($groupby_results)): ?>
        <div class="col-12 col-md-auto p-1">
          <select class="form-select" id="SumBy" name="SumBy[field]">
            <option selected="selected" value="">Sumar</option>
            <?php if (isset($array_headers['headers']) && is_array($array_headers['headers'])): ?>
            <?php foreach ($array_headers['headers'] as $key_headers => $row_headers) { ?>
              <option value="<?php echo $row_headers; ?>"><?php echo getFieldAlias($row_headers); ?></option>
            <?php } ?>
            <?php endif; ?>
          </select>
        </div>
        <?php endif; ?>
        <!-- limit records -->
        <div class="col-12 col-md-auto p-1">
          <select class="form-select" id="Limit" name="Limit">
            <?php foreach ($array_limit as $key_limit => $row_limit) { ?>
              <option <?php if($Limit == $row_limit) echo 'selected="selected"'; ?> value="<?php echo $row_limit ?>"><?php echo $row_limit ?> Registros</option>
            <?php } ?>
          </select>
        </div>
        <!-- buttons -->
        <div class="col-12 col-md-auto p-1">
          <div class="btn-group">
            <button class="btn btn-subtle-primary" type="submit" name="action" value="Mostrar">Aplicar</button>
            <button class="btn btn btn-subtle-success" type="submit" name="action" value="excel">Excel</button>
          </div>
        </div>
        <!-- badge area -->
        <div class="col-md-12 p-1">
          <!-- filters remove -->
          <?php foreach ($filter_results as $filter_key => $filter_value) { ?>
            <input type="hidden" name="filter_selected[<?php echo $filter_key; ?>][filter][<?php echo $filter_value['key']; ?>]" value="<?php echo $filter_value['value']; ?>">
            <input type="hidden" name="filter_selected[<?php echo $filter_key; ?>][operator]" value="<?php echo $filter_value['operator']; ?>">
            <span class="badge badge-phoenix fs-11 badge-phoenix-primary">
              <span class="badge-label"><?php echo $filter_value['key']; ?> <?php echo $filter_value['operator']; ?> <?php echo $filter_value['value']; ?></span>
              <button type="submit" name="unset[<?php echo $filter_value['key']; ?>]" value="<?php echo $filter_value['value']; ?>" class="btn btn-link p-0 ms-1" style="height:18px;width:12.8px;">x</button>
            </span>
          <?php } ?>
          <!-- group remove -->
          <?php foreach ($groupby_results as $groupby_key => $groupby_value) { ?>
            <input type="hidden" name="groupby_selected[<?php echo $groupby_key; ?>][<?php echo $groupby_value['key']; ?>]" value="<?php echo $groupby_value['value']; ?>">
            <span class="badge badge-phoenix fs-11 badge-phoenix-danger">
              <span class="badge-label"><?php echo $groupby_value['key']; ?> <?php echo $groupby_value['value']; ?></span>
              <button type="submit" name="unset[<?php echo $groupby_value['key']; ?>]" value="<?php echo $groupby_value['value']; ?>" class="btn btn-link p-0 ms-1" style="color:#bc3803;height:18px;width:12.8px;">x</button>
            </span>
          <?php } ?>
          <!-- sum remove -->
          <?php if (isset($sumby_results) && is_array($sumby_results)): ?>
          <?php foreach ($sumby_results as $sumby_key => $sumby_value) { ?>
            <input type="hidden" name="sumby_selected[<?php echo $sumby_key; ?>][<?php echo $sumby_value['key']; ?>]" value="<?php echo $sumby_value['value']; ?>">
            <span class="badge badge-phoenix fs-11 badge-phoenix-warning">
              <span class="badge-label">Sumar: <?php echo $sumby_value['value']; ?></span>
              <button type="submit" name="unset[<?php echo $sumby_value['key']; ?>]" value="<?php echo $sumby_value['value']; ?>" class="btn btn-link p-0 ms-1" style="color:#856404;height:18px;width:12.8px;">x</button>
            </span>
          <?php } ?>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>
  <?php } ?>

<script>
function disableButtons(form) {
    setTimeout(() => {
        let buttons = form.querySelectorAll("button");
        buttons.forEach(btn => btn.disabled = true);

        // Reactivar los botones después de 3 segundos
        setTimeout(() => {
            buttons.forEach(btn => btn.disabled = false);
        }, 5000);

    }, 100);
}



</script>