<?php

namespace Stevebauman\Maintenance\Http\Controllers;

use Stevebauman\Maintenance\Http\Requests\Auth\RegisterRequest;
use Stevebauman\Maintenance\Http\Requests\Auth\LoginRequest;
use Stevebauman\Corp\Facades\Corp;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;

class AuthController extends Controller
{
    /**
     * Displays the login page.
     *
     * @return \Illuminate\View\View
     */
    public function authenticate()
    {
        return view('maintenance::auth.login.index');
    }

    /**
     * Processes logging in a user.
     *
     * @param LoginRequest $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request)
    {
        try
        {
            $input = $request->all();

            $remember = (bool) array_pull($input, 'remember', false);

            if ($auth = Sentinel::authenticate($input, $remember))
            {
                $message = 'Successfully logged in.';

                return redirect()->intended(route('maintenance.dashboard.index'))->withSuccess($message);
            } else if(Corp::auth($input['email'], $input['password']))
            {
                $user = Corp::user($input['email']);

                $name = explode(',', $user->name);

                $credentials = [
                    'email' => $user->email,
                    'username' => $user->username,
                    'password' => $input['password'],
                    'first_name' => (array_key_exists(1, $name) ? $name[1] : null),
                    'last_name' => (array_key_exists(0, $name) ? $name[0] : null),
                ];

                return $this->registerAndAuthenticateUser($credentials);
            }

            $errors = 'Invalid login or password.';
        }
        catch (NotActivatedException $e)
        {
            $errors = 'Account is not activated!';
        }
        catch (ThrottlingException $e)
        {
            $delay = $e->getDelay();

            $errors = "Your account is blocked for {$delay} second(s).";
        }

        return Redirect::back()->withInput()->withErrors($errors);
    }

    /**
     * Show the form for registering an account.
     *
     * @return \Illuminate\View\View
     */
    public function getRegister()
    {
        return view('maintenance::auth.register.index');
    }

    /**
     * Processes registering for an account.
     *
     * @param RegisterRequest $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function postRegister(RegisterRequest $request)
    {
        $data = $request->all();

        /*
         * We'll create a random unique username since
         * the username attribute is only for LDAP logins
         */
        $data['username'] = uniqid();

        // Create the user with default groups of all users and customers
        if ($this->sentry->registerUser($data, ['all_users', 'customers'])) {
            $message = 'Successfully created account. You can now login.';

            return redirect()->route('maintenance.login')->withSuccess($message);
        } else {
            $message = 'There was an issue registering you an account. Please try again.';

            return redirect()->route('maintenance.login')->withErrors($message);
        }
    }

    /**
     * Logs the user out.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Sentinel::logout();

        $message = 'Successfully logged out.';

        return redirect()->route('maintenance.login')->withSuccess($message);
    }

    /**
     * Registers and authenticates a user by the specified credentials.
     *
     * @param array $credentials
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function registerAndAuthenticateUser(array $credentials)
    {
        $model = Sentinel::createModel();

        // See if the LDAP user already has an account first
        $user = $model->where('email', $credentials['email'])->first();

        if($user)
        {
            // Update the user
            Sentinel::update($user, $credentials);

            // Log them in
            Sentinel::login($user);

            $message = 'Successfully logged in.';

            return redirect()->intended('/')->withSuccess($message);
        } else
        {
            $user = Sentinel::registerAndActivate($credentials);

            if($user) {
                Sentinel::login($user);

                $message = 'Successfully logged in.';

                return redirect()->intended('/')->withSuccess($message);
            }
        }

        $message = 'There was an issue creating your active directory account. Please try again.';

        return redirect()->route('maintenance.login')->withErrors($message);
    }
}
