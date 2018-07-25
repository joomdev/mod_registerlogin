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
class modRegisterLoginHelper
{
	
	// ajax functionality of module	
	public static function getUserLoginAjax(){
		$username = JRequest::getVar('username');
		$password = JRequest::getVar('password');
		$remember = JRequest::getVar('remember');
		
		$result = JFactory::getApplication()->login(array('username'=>$username,'password'=>$password), array('remember' => $remember));$userid = JFactory::getUser()->id;				
		if(isset($userid) && !empty($userid)){
			$data['message']  = '<div class="alert alert-success"><a data-dismiss="alert" class="close">x</a><div><p>'.JText::_('MOD_REGISTERLOGIN_LOGIN_SUCCESS').'</p></div></div>';
			$data['success']  = true;
		}else{
			$data['message']  = '<div class="alert alert-warning"><a data-dismiss="alert" class="close">x</a><div><p>'.JText::_('MOD_REGISTERLOGIN_WRONG_LOGIN_MESSAGE').'</p></div></div>';
			$data['success']  = false;
		}
		echo json_encode($data);
		exit;
		
	}
	
	public static function getUserlogin($params = array()){
		$username = JRequest::getVar('username');
		$password = JRequest::getVar('password');
		$remember = JRequest::getVar('remember');				
		$result = JFactory::getApplication()->login(array('username'=>$username,'password'=>$password), array('remember' =>$remember));  
		if(isset($result) && !empty($result)){
			$response['error']			=	false;
			$response['error_message']	=  JText::_('MOD_REGISTERLOGIN_LOGIN_SUCCESS');		
		}else{
			$response['error']			=	true;
			$response['error_message']	= 	JText::_('MOD_REGISTERLOGIN_WRONG_LOGIN_MESSAGE');
		}
		return $response;	
	}
	
	// ajax registeration	
	public static function getUserRegisterAjax($params = array()){		
		$language = JFactory::getLanguage();
		$language->load('com_users');	
		$app	 			= JFactory::getApplication();
		$userParams    		=  JComponentHelper::getParams('com_users');
		$data 				= $app->input->post->get('jform', array(), 'array');
		$user 				= new JUser;
		
		
		jimport( 'joomla.application.module.helper' );
		$module = JModuleHelper::getModule('mod_registerlogin');
		$params = new JRegistry($module->params);
		$publickey 	 = $params->get('public');
		$privatekey	 = $params->get('private');
		
		//check captcha validat
		$captch_enable = $params->get('enablecap_on_register');
		if($captch_enable){
			if (isset($_REQUEST["recaptcha_response_field"]) && !empty($_REQUEST["recaptcha_response_field"])) {
				JPluginHelper::importPlugin('captcha');
				$dispatcher = JDispatcher::getInstance();
				$resp = $dispatcher->trigger('onCheckAnswer',$_REQUEST['recaptcha_response_field']);

				if (!$resp[0]) {
					$data['message']  = '<div class="alert alert-error"><a data-dismiss="alert" class="close">x</a><div><p>'.JText::_('MOD_REGISTERLOGIN_CAPTCHA_ERROR').'</p></div></div>';
					$data['success']  = false;
					echo json_encode($data);
					exit;	
				}
			}
		}	
				
		JPluginHelper::importPlugin('user');
		
		$data['email'] 		= JStringPunycode::emailToPunycode($data['email1']);
		$data['password'] 	= $data['password1'];
		
		$useractivation 	= $params->get('useractivation');
		$sendpassword 		= $userParams->get('sendpassword', 1);
		
		if (($useractivation == 1) || ($useractivation == 2))
		{
			$data['activation'] = JApplicationHelper::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}else{
			$data['activation'] = 0;
			$data['block'] = 0;
		}		

		// Bind the data.
		if (!$user->bind($data))
		{
			
			
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save())
		{
			
			if($user->getError() == 'Username in use.'){
				$errorMessage  = JText::_('COM_USERS_REGISTER_USERNAME_MESSAGE');
			}else{
				$errorMessage  = $user->getError();
			}			
			$data['message']  = '<div class="alert alert-warning"><a data-dismiss="alert" class="close">x</a><div><p>'.$errorMessage.'</p></div></div>';
			$data['success']  = false;
			echo json_encode($data);
			exit;
		}else{
			$id 		= $user->id;
			$gId 		= $userParams->get('new_usertype');
			
			$db    		=  JFactory::getDBO();
			$query 		= $db->getQuery(true);
			
			$columns = array('user_id', 'group_id');
			$values = array($id,$gId);
			$query
				->insert($db->quoteName('#__user_usergroup_map'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));			
			$db->setQuery($query);
			$db->execute();
			
			
			$config = JFactory::getConfig();
			$query = $db->getQuery(true);

			// Compile the notification mail values.
			$data = $user->getProperties();
			$data['fromname'] = $config->get('fromname');
			$data['mailfrom'] = $config->get('mailfrom');
			$data['sitename'] = $config->get('sitename');
			$data['siteurl'] = JUri::root();

			// Handle account activation/confirmation emails.
			if ($useractivation == 2)
			{
				// Set the link to confirm the user email.
				$uri = JUri::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username']
					);
				}
			}
			elseif ($useractivation == 1)
			{
				// Set the link to activate the user account.
				$uri = JUri::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username']
					);
				}
			}
			else
			{
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_BODY',
						$data['name'],
						$data['sitename'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['siteurl']
					);
				}
			}
			
			
			// Send the registration email.
			$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

			// Send Notification mail to administrators
			if (($params->get('useractivation') < 2) && ($params->get('mail_to_admin') == 1))
			{
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				$emailBodyAdmin = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
					$data['name'],
					$data['username'],
					$data['siteurl']
				);

				// Get all admin users
				$query->clear()
					->select($db->quoteName(array('name', 'email', 'sendEmail')))
					->from($db->quoteName('#__users'))
					->where($db->quoteName('sendEmail') . ' = ' . 1);

				$db->setQuery($query);

				try
				{
					$rows = $db->loadObjectList();
				}
				catch (RuntimeException $e)
				{
					$app->setError(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);

					return false;
				}

				// Send mail to all superadministrators id
				foreach ($rows as $row)
				{
					$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);
				}
			}
			
			
			if ($useractivation == 0)
			{
				$messge = JText::_('COM_USERS_REGISTRATION_SAVE_SUCCESS');
			}
			elseif ($useractivation == 1)
			{
				$messge = JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE');
			}else{
				$messge = JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY');
			}
			
			$data['message']  = '<div class="alert alert-success"><a data-dismiss="alert" class="close">x</a><div><p>'.$messge.'</p></div></div>';
			$data['success']  = true;
			echo json_encode($data);
			exit;
		
		}
	
	}
	
	
	// register user without ajax 
	public static function getUserRegister($params = array()){
	
		$app	 			= JFactory::getApplication();
		$session	 		= JFactory::getApplication();
		$userParams    		=  JComponentHelper::getParams('com_users');
		$data 				= $app->input->post->get('jform', array(), 'array');
		$user 				= new JUser;
		$flag				= true;
		$response			= array();
		jimport( 'joomla.application.module.helper' );
		$module = JModuleHelper::getModule('mod_registerlogin');
		$params = new JRegistry($module->params);
		$publickey 	 = $params->get('public');
		$privatekey	 = $params->get('private');
		
		//check captcha validat
		
		$captch_enable = $params->get('enablecap_on_register');
		if($captch_enable){
			if (isset($_POST["recaptcha_response_field"]) && !empty($_POST["recaptcha_response_field"])) {
				JPluginHelper::importPlugin('captcha');
				$dispatcher = JDispatcher::getInstance();
				$resp = $dispatcher->trigger('onCheckAnswer',$_REQUEST['recaptcha_response_field']);

				if (!$resp[0]) {
					$flag = false;
					$response['error']			=	true;
					$response['error_message']	=  JText::_('MOD_REGISTERLOGIN_CAPTCHA_ERROR');			 
					return $response; 
				} 
			}
		}		
		
		JPluginHelper::importPlugin('user');		
		$data['email'] 		= JStringPunycode::emailToPunycode($data['email1']);
		$data['password'] 	= $data['password1'];
		$useractivation 	= $params->get('useractivation');
		$sendpassword 		= $userParams->get('sendpassword', 1);
		
		if (($useractivation == 1) || ($useractivation == 2))
		{
			$data['activation'] = JApplicationHelper::getHash(JUserHelper::genRandomPassword());
			$data['block'] = 1;
		}else{
			$data['activation'] = 0;
			$data['block'] = 0;
		}		

		// Bind the data.
		if (!$user->bind($data))
		{
			
			
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save())
		{
			$response['error']			=  true;
			$response['error_message']	=  $user->getError();			 
			return $response;
		}else{
			$id 		= $user->id;
			$gId 		= $userParams->get('new_usertype');
			
			$db    		=  JFactory::getDBO();
			$query 		= $db->getQuery(true);
			
			$columns = array('user_id', 'group_id');
			$values = array($id,$gId);
			$query
				->insert($db->quoteName('#__user_usergroup_map'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));			
			$db->setQuery($query);
			$db->execute();
			
			
			$config = JFactory::getConfig();
			$query = $db->getQuery(true);

			// Compile the notification mail values.
			$data = $user->getProperties();
			$data['fromname'] = $config->get('fromname');
			$data['mailfrom'] = $config->get('mailfrom');
			$data['sitename'] = $config->get('sitename');
			$data['siteurl'] = JUri::root();

			// Handle account activation/confirmation emails.
			if ($useractivation == 2)
			{
				// Set the link to confirm the user email.
				$uri = JUri::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username']
					);
				}
			}
			elseif ($useractivation == 1)
			{
				// Set the link to activate the user account.
				$uri = JUri::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
				$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['activate'],
						$data['siteurl'],
						$data['username']
					);
				}
			}
			else
			{
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				if ($sendpassword)
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_BODY',
						$data['name'],
						$data['sitename'],
						$data['siteurl'],
						$data['username'],
						$data['password_clear']
					);
				}
				else
				{
					$emailBody = JText::sprintf(
						'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
						$data['name'],
						$data['sitename'],
						$data['siteurl']
					);
				}
			}
			
			
			// Send the registration email.
			$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

			// Send Notification mail to administrators
			if (($params->get('useractivation') < 2) && ($params->get('mail_to_admin') == 1))
			{
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				);

				$emailBodyAdmin = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
					$data['name'],
					$data['username'],
					$data['siteurl']
				);

				// Get all admin users
				$query->clear()
					->select($db->quoteName(array('name', 'email', 'sendEmail')))
					->from($db->quoteName('#__users'))
					->where($db->quoteName('sendEmail') . ' = ' . 1);

				$db->setQuery($query);

				try
				{
					$rows = $db->loadObjectList();
				}
				catch (RuntimeException $e)
				{
					$app->setError(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);

					return false;
				}

				// Send mail to all superadministrators id
				foreach ($rows as $row)
				{
					$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);

					// Check for an error.
					if ($return !== true)
					{
						$app->setError(JText::_('MOD_REGISTERLOGIN_REGISTRATION_ACTIVATION_NOTIFY_SEND_MAIL_FAILED'));

						return false;
					}
				}
			}
		
		}
	
	}
	
	public static function getReturnURL($params, $type)
	{
		$app	= JFactory::getApplication();
		$item   = $app->getMenu()->getItem($params->get($type));

		if ($item)
		{
			$vars = $item->query;
		}
		else
		{
			// Stay on the same page
			$vars = $app::getRouter()->getVars();
		}
		
		return 'index.php?' . JUri::buildQuery($vars);
	}
	
	public static function getType()
	{
		$user = JFactory::getUser();

		return (!$user->get('guest')) ? 'logout' : 'login';
	}
}
