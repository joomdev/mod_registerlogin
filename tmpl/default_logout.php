<?php
/**
 * @package		Login Register module for joomla
 * @subpackage  mod_loginregister
 * @author		www.joomdev.com
 * @author		Created on March 2016
 * @copyright	Copyright (C) 2009 - 2016 www.joomdev.com. All rights reserved.
 * @license		GNU GPL2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.keepalive');
if($params->get('logout')){
	$afterlogout_ = JURI::Root().$return;
}else{
	$afterlogout_ = JRoute::_(JURI::root());
}
?>
<form action="<?php echo JRoute::_(JUri::getInstance()->toString(), true, $params->get('usesecure')); ?>" method="post" id="login-form" class="form-vertical">
<?php if ($params->get('greeting')) : ?>
	<div class="login-greeting">
	<?php if ($params->get('name') == 0) : {
		echo JText::sprintf('MOD_REGISTERLOGIN_HINAME', htmlspecialchars($user->get('name')));
	} else : {
		echo JText::sprintf('MOD_REGISTERLOGIN_HINAME', htmlspecialchars($user->get('username')));
	} endif; ?>
	</div>
<?php endif; ?>
	<div class="logout-button">
		<input type="submit" name="Submit" class="btn btn-primary" value="<?php echo JText::_('JLOGOUT'); ?>" />
		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.logout" />
		<input type="hidden" name="return" value="<?php echo base64_encode($afterlogout_); ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
