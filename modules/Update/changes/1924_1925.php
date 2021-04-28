<?php

/* crmv@192216 */

@unlink('themes/softed/vte_material.css');
@unlink('themes/softed/select2.css');
@unlink('themes/softed/bootstrap-custom.css');
@unlink('themes/softed/bootstrap.min.css');
@unlink('themes/softed/scss/select2.scss');
FSUtils::deleteFolder('themes/softed/js/select2');

@unlink('themes/next/vte_material.css');
@unlink('themes/next/select2.css');
@unlink('themes/next/bootstrap-custom.css');
@unlink('themes/next/bootstrap.min.css');
@unlink('themes/next/scss/select2.scss');
FSUtils::deleteFolder('themes/next/js/select2');