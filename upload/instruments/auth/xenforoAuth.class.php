<?php

class XenforoAuth extends AuthAbstract implements AuthInterface
{
    private $xfWay;
    private $sync = false;
    
    public function __construct()
    {
        global $site_ways;  
        
        if (isset($cfg['p_sync'])) {
            $this->sync = $cfg['p_sync'];
        }     
        
        $this->xfWay = $site_ways['main_cms'];
        
        if (!class_exists('XenForo_Autoloader')) {

            if (empty($this->xfWay))
                exit('[MCMS] Не проинициализирован путь до дирректории Xenforo, '
                    . 'проверьте опцию $site_ways[\'main_cms\'] в настройках скрипта авторизации.');

            if (!file_exists($this->xfWay . 'library/XenForo/Autoloader.php'))
                exit('[MCMS] Файл "' . $this->xfWay . 'library/XenForo/Autoloader.php" отсутствует. '
                    . 'Путь до дирректории Xenforo указан не верно, проверьте опцию $site_ways[\'main_cms\'] '
                    . 'в настройках скрипта авторизации.');

            require($this->xfWay . '/library/XenForo/Autoloader.php');

            XenForo_Autoloader::getInstance()->setupAutoloader($this->xfWay . 'library');
            XenForo_Application::initialize($this->xfWay . 'library', $this->xfWay);
            XenForo_Application::set('page_start_time', microtime(true)); 
        }   
    }
    
    public function xfGetUserId()
    {
        XenForo_Session::startPublicSession();
        $visitor = XenForo_Visitor::getInstance();

        return $visitor->getUserId();
    }
    
    public function onUserLogin($user)
    {
        $xfUserId = $this->xfGetUserId(); 
        $userId = $user->id();
        
        if (!$this->sync or ($xfUserId and $xfUserId == $userId))
            return;

        $loginModel = XenForo_Model::create('XenForo_Model_Login');
        $userModel = XenForo_Model::create('XenForo_Model_User');

        $userModel->setUserRememberCookie($userId);

        XenForo_Model_Ip::log($userId, 'user', $userId, 'login');

        $userModel->deleteSessionActivity(0, GetRealIp());

        $session = XenForo_Application::get('session');
        $session->changeUserId($userId);
        XenForo_Visitor::setup($userId);
    }
    
    public function onUserLogout($user)
    {
        $xfUserId = $this->xfGetUserId();  
        $userId = $user->id();
        
        if (!$this->sync or !$xfUserId or $xfUserId != $userId)
            return;

        if (XenForo_Visitor::getInstance()->get('is_admin')) {

            $adminSession = new XenForo_Session(array('admin' => true));
            $adminSession->start();

            if ($adminSession->get('user_id') == XenForo_Visitor::getUserId())
                $adminSession->delete();
        }

        XenForo_Model::create('XenForo_Model_Session')->processLastActivityUpdateForLogOut(XenForo_Visitor::getUserId());

        XenForo_Application::get('session')->delete();
        XenForo_Helper_Cookie::deleteAllCookies(
                array('session'), array('user' => array('httpOnly' => false))
        );

        XenForo_Visitor::setup(0);
    }

    public function userLoad()
    {
        $user = parent::userLoad(); // Restore mcr user if authed

        if ($this->sync) { // check auth state in xenForo if needed
            $id = $this->xfGetUserId();

            if ($user and
                    (($id and $id != $user->id()) or !$id)
            ) {
                $user->logout();
                unset($user);
                $user = null;
            }

            if ($id) {
                $user = new User($id);
                
                if (!$user->exist() or $user->lvl() <= 0)
                    $user = null;
                else
                    $user->login();
            }
        }

        return $user;
    }
}
