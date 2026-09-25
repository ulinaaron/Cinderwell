<?php
$redirect = $attributes['redirectAfterLogin'] ?? '';
echo \Cinderwell_Portal\Template_Loader::load('login-form', ['redirect' => $redirect]);
