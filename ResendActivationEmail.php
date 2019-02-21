<?php
/**
 * @package    ResendActivationEmail
 *
 * @author     tony <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

/**
 * PlgResendActivationEmail plugin.
 *
 * @package  plgResendActivationEmail
 * @since    1.0
 */
class plgSystemResendActivationEmail extends CMSPlugin
{
    /**
     * Application object
     *
     * @var    CMSApplication
     * @since  1.0
     */
    protected $app;

    /**
     * Database object
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    protected $db;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  1.0
     */
    protected $autoloadLanguage = true;

    public function onAfterInitialise() {
        $app	= JFactory::getApplication();
	$input	= $app->input;

	$username	= $input->getString('username', '');
	$resend		= $input->getInt('resendactivation', 0);
	
	// Activation request 
	if ($resend === 1 && $username !== '') {
		$user = JFactory::getUser($username);
		$sendmail = plgSystemResendActivationEmail::resendActivationEmail($user);
		
		if ($sendmail) {
			$message = "We have sent you your activation email, please keep check your email. Do not forget to check your spam also.";
			$url = JRoute::_('index.php?option=com_users&view=login');
			$app->redirect($url, $message, 'Success');
			return false;
		} else {
			$message = "There was a problem sending your activation email, please contact us.";
			$url = JRoute::_('index.php?option=com_users&view=login');
			$app->redirect($url, $message, 'Error');
			return false;
		}
	}
    }

	public function onUserAuthorisation($user, $options) {

		// Getuser
		$gotuser = JFactory::getUser($user->username);

		if ($gotuser &&  strlen($gotuser->activation) > 1) {
			// Account is not yet activated. 
			// Offer to send activation email.

			plgSystemResendActivationEmail::killMessage('');
			$app = JFactory::getApplication();
			$message = "Your account has not been activated yet. <strong><a href='index.php?option=com_users&view=login&resendactivation=1&username=$gotuser->username'>CLICK HERE</a></strong> to <strong>resend</strong> your activation email.";
			$url = JRoute::_('index.php?option=com_users&view=login');
			$app->redirect($url, $message, 'Error');

			return false;
		}

	}

        function killMessage($error) {
            $app = JFactory::getApplication();
            $appReflection = new ReflectionClass(get_class($app));
            $_messageQueue = $appReflection->getProperty('_messageQueue');
            $_messageQueue->setAccessible(true);
            $messages = $_messageQueue->getValue($app);
            foreach($messages as $key=>$message) {
                if($message['message'] == $error) {
                    unset($messages[$key]);
                }
            }
            $_messageQueue->setValue($app,$messages);
	
        }

	/**
	 * Send activation email to a provided user.
	 *
	 * @param JUser $user
	 */
	private static function resendActivationEmail($user)
	{
		$config = JFactory::getConfig();

		// load com_users language strings
		$jlang = JFactory::getLanguage();
		$jlang->load('com_users', JPATH_ROOT, 'en-GB', true); // Load English (British)
		$jlang->load('com_users', JPATH_ROOT, $jlang->getDefault(), true); // Load the site's default language
		$jlang->load('com_users', JPATH_ROOT, null, true); // Load the currently selected language

		// build message
		$activationLink = str_replace('/administrator', '', JUri::root()) . 'index.php?option=com_users&task=registration.activate&token=' . $user->activation;

		$emailSubject = JText::sprintf(
			'COM_USERS_EMAIL_ACCOUNT_DETAILS',
			$user->name,
			$config->get('sitename')
		);

		$emailBody = JText::sprintf(
			'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
			$user->name,
			$config->get('sitename'),
			$activationLink,
			JUri::root(),
			$user->username
		);

		// send it
		$sent = JFactory::getMailer()->sendMail($config->get('mailfrom'), $config->get('fromname'), $user->email, $emailSubject, $emailBody);

		var_dump($sent);die;

		return $sent;
	}
}
