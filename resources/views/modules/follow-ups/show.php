<?php
$actions = '';
require __DIR__ . '/../../components/page-header.php';
?>
<div class="card content-card"><div class="card-body"><pre class="mb-0"><?php foreach (get_defined_vars() as $k=>$v) { if (is_array($v) && !in_array($k, ['_SESSION','_SERVER'])) { echo e(ucwords(str_replace('_',' ',$k)))."\n"; foreach($v as $kk=>$vv){ if(!is_array($vv)) echo e("$kk: $vv")."\n"; } break; } } ?></pre></div></div>
