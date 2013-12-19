<?php

namespace pew\controllers;

/**
 * An example Users controller, to demonstrate common login and logout
 * procedures.
 *
 * This assumes a users table with username and password fields.
 * 
 * @package pew/default/controllers
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Users extends \pew\Controller
{
    /**
     * The login action displays a login form and validates its input
     *
     * @access public
     */
    public function login()
    {
        # check if a login form was submitted
        if ($post = $this->request->post()) {
            # check if the user exists
            if ($user = $this->model->find_by_username($post['username'])) {
                # check if passwords match
                if ($user['password'] === crypt($post['password'], $user['password'])) {
                    unset($user['password']);
                    $this->session->user = $user->attributes();
                    redirect('');
                }
            }

            # wrong username or password
            $this->session->flash('login error', 'Invalid username or password');
            redirect('users/login');
        }
        
        # the login view will be displayed if execution reaches this point
    }

    /**
     * The signup action displays a form for user to create an account.
     */
    public function signup()
    {
        # check if a login form was submitted
        if ($post = $this->request->post()) {
            # check that the username is available
            if (!$this->model->find_by_username($post['username'])) {
                # check password length if passwords match
                if (strlen($post['password']) >= 6) {
                    if ($post['password'] === $post['password_confirm']) {
                        $post['password'] = crypt($post['password']);
                        $post['created'] = time();
                        $this->model->save($post);
                        redirect('users/login');
                    } else {
                        # password and password_confirm fields do not match
                        $this->session->flash('signup error', 'Passwords do not match');
                        redirect('users/signup');
                    }
                } else {
                    # the password is too short
                    $this->session->flash('signup error', 'Password must be at least 6 characters long');
                    redirect('users/signup');
                }
            } else {
                # wrong username or password
                $this->session->flash('signup error', 'Invalid username');
                redirect('users/signup');
            }
        }
        
        # the signup view will be displayed if execution reaches this point
    }
    
    /**
     * The logout action resets the authentication for the current session.
     */
    public function logout()
    {
        # clear authentication status
        unset($this->session->user);

        # and display the default controller/action
        redirect('');
    }
}
