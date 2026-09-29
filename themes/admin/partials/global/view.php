<?php $this->layout('layout::crudTableLayout', ['data' => $data ?? []]) ?>
    <!-- Top bar start -->
    <div id="mainTop">
        <h1><?=$this->t($data['pageEntity'])?></h1>
        <?php if(isset($data['createButton']) && $data['createButton'] === true):?>
            <button class="btn primary" id="create"><?=$this->t($data['titleCreate'])?></button>
        <?php endif;?>
    </div>
    <!-- Top bar end -->
<?php if ($this->engine->exists('partialsGlobal::crudTable')) {
    $tpl = $this->fetch('partialsGlobal::crudTable', ['data' => $data]);
} else {
    $tpl = $this->fetch('partialsGlobalDefault::crudTable', ['data' => $data]);
}?>
<?=$this->section('crudTable', $tpl)?>