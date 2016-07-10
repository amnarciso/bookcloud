<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

use Application\Model\Users;

/**
 * ConfigController
 *
 * @author
 *
 * @version
 *
 */
class AdminController extends AbstractActionController {
    protected $logged;
    protected $user;
    
    /** 
     * Controller Constructor
     * 
     * Make login information available
     */
    public function __construct()
    {
        $user_session = new Container('user');
        $this->logged = $user_session->logged;
        $this->user = $user_session->user;
    }
	
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {       
        //Redirecting if not a logged admin
        if ((!$this->logged) || (!$this->user->admin))
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
		
        //Get last hash
        $sm = $this->getServiceLocator();
        $users = new Users($sm); 
        $usersList = $users->listUsers();

        //Load view
        return new ViewModel(array(
                'nav'      => 4,
                'user'     => $this->user,
                'usersList'=> $usersList,
        ));
	}

    public function loginAction() {
        //Redirecting if not a logged admin
        if ((!$this->logged) || (!$this->user->admin))
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
        
        //Initiate variables
        $sm = $this->getServiceLocator();
        $users = new Users($sm);
        $userId = (int) $this->params()->fromRoute('id', 0);
        
        //Load user information
        $user_session = new Container('user');
        $user = $users->userDetails($userId);
        $user->admin = true;
        $user_session->user = $user;

        //Redirect to home
        return $this->redirect()->toRoute('application/default', array(
            'controller' => 'index',
            'action'     => 'index'
            ));
    }

    public function statusAction() {
        //Redirecting if not a logged admin
        if ((!$this->logged) || (!$this->user->admin))
            return $this->redirect()->toRoute('application/default', array(
                'controller' => 'index',
                'action'     => 'index'
                ));
        
        //Initiate variables
        $sm = $this->getServiceLocator();
        $users = new Users($sm);
        $userId = (int) $this->params()->fromRoute('id', 0);
        $status = $this->params()->fromRoute('arg', 0);
        
        //Load user
        $user = $users->userDetails($userId);

        //Set new status
        if ($user->status == 'active'){
            $user->status = 'inactive';
        } else {
            $user->status = 'active';
        }

        //Save user
        $users->update($user);

        //Redirect to admin tab
        return $this->redirect()->toRoute('application/default', array(
            'controller' => 'admin',
            'action'     => 'index'
            ));

    }
}