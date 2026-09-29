<div class="row">
    <div class="col-lg-12">
    <?php if ($data['model']):
        $translationId = $data['model']->getId(); ?>
        <h1 class="page-header"><?=$this->t('Edit translation:')?> <?=$data['model']->getId()?></h1>
    <?php else:
        $translationId = 'null'; ?>
        <h1 class="page-header"><?=$this->t('Create translation')?></h1>
    <?php endif;?>
    </div>
</div>
<?php
$actionBase = '/translator';
if ($data['adminPath'] !== '') {
    $actionBase = sprintf('/%s/translator', $data['adminPath']);
}
$action = sprintf('%s/%s/', $actionBase, 'create');
if ($data['model']) {
    $action = sprintf('%s/%s/%s/', $actionBase, 'update', $data['model']->getId());
}
?>
<form action="<?=$action?>" class="inlineForm" method="POST" enctype="multipart/form-data" autocomplete="off">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <p>Language: <?=$data['model']?->getLanguage()?></p>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group divUser">
                                <label class="userLabel"><?=$this->t('Original string')?></label>
                                <input name="originalString" value="<?=$data['model']?->getOriginalString() ?>" readonly class="form-control inputDesign" />
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group divUser">
                                <label class="userLabel"><?=$this->t('Translated string')?></label>
                                <input name="translatedString" value="<?=$data['model']?->getTranslatedString()?>" class="form-control inputDesign" />
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group divUser">
                                <label class="userLabel"><?=$this->t('Group')?></label>
                                <input name="group" value=" <?=$data['model']?->getGroup()?>" class="form-control inputDesign" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <input type="hidden" name="translationId" value="<?=$data['model']?->getId()?>" />
                            <input data-action="<?=$action?>" type="submit" value="<?=$this->t('Submit')?>" class="btn btn-success submitButtonSize" id="submitButton" />
                        </div>
                    </div>
                    <!-- /.row (nested) -->
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.row -->
    </div>
</form>