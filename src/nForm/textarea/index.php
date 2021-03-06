<?php
if(empty($this->nform_settings))
    return;

$required    =    (!empty($this->nform_settings['other']) && (stripos($this->nform_settings['other'], 'required') !== false));
?>
    <div class="nbr_form_input wrap">
        <?php if(!empty($this->nform_settings['label'])) { ?><label<?php if($required) echo ' class="required"' ?>><span class="nbr_sub_label"><?php echo $this->nform_settings['label']; ?></span><?php } ?><?php if(!empty($this->nform_settings['label']) && !$this->labelWrap) { ?></label><?php } ?>
            <div class="nbr_form_input_cont<?php echo (!empty($this->nform_settings['wrap_class']))? ' '.$this->nform_settings['wrap_class'] : ''; ?>">
                <textarea name="<?php echo $this->nform_settings['name']; ?>"<?php echo $this->nform_settings['other'].$this->nform_settings['id'].$this->nform_settings['class'].$this->nform_settings['style'].$this->nform_settings['disabled'].$this->nform_settings['placeholder']; ?>><?php echo $this->nform_settings['value']; ?></textarea>
            </div>
        <?php if(!empty($this->nform_settings['label']) && $this->labelWrap) { ?></label><?php } ?>
    </div>