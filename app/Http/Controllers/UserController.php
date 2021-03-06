<?php namespace App\Http\Controllers;

use App\Models\NotificationType;
use App\Repositories\Notification\NotificationRepository;
use App\Repositories\Post\PostRepository;
use App\Repositories\Role\RoleRepository;
use App\Repositories\User\UserRepository;
use App\Services\CollectionService;
use App\Models\Social;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Services\Analytics;
use Laravel\Socialite\Contracts\Factory as Socialite;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller
{

    /**
     * Constructor for UserController.
     *
     * @param UserRepository $user
     */
    public function __construct(UserRepository $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    /**
     * Sets if user only would like to view there own codeblocks.
     *
     * @return mixed
     */
    public function setOnly()
    {
        if (Auth::check()) {
            Auth::user()->setOnly();
        }

        return Redirect::back();
    }

    /**
     * Render index view for user.
     *
     * @permission view_users
     * @return object     view object.
     */
    public function index()
    {
        return View::make('user.index')->with('title', 'Users')->with('users', $this->user->get());
    }

    /**
     * Render view for create user.
     *
     * @return object     view object.
     */
    public function create()
    {
        return View::make('user.create')->with('title', Lang::get('app.userCreate'));
    }

    /**
     * Render backup view.
     *
     * @return mixed
     */
    public function backup()
    {
        return View::make('user.backup')
            ->with('title', 'Backup codeblocks')
            ->with('json', Auth::user()->posts->toJson());
    }

    /**
     * Stores user.
     *
     * @param RoleRepository $role
     * @param null $id
     *
     * @return mixed
     */
    public function store(RoleRepository $role, $id = null)
    {
        $input = $this->request->all();
        if (is_null($id)) {
            try {
                $input['role'] = $role->getDefault()->id;
            } catch (\Exception $e) {
                $input['role'] = 1;
            }
        } else {
            if ($id != Auth::user()->id) {
                Redirect::back()->with('error', 'You can not change other users information.');
            }
        }
        if ($this->user->createOrUpdate($input, $id)) {
            if (is_null($id)) {
                return Redirect::back()
                    ->with('success', 'Your user has been created, use the link in the mail to activate your user.');
            } else {
                return Redirect::back()->with('success', 'Your user has been saved.');
            }
        }

        return Redirect::back()
            ->withInput($this->request->except('password'))
            ->withErrors($this->user->getErrors());
    }

    /**
     * Render view to display a user.
     *
     * @param  int $id id for user to display.
     *
     * @return object     view object.
     */
    public function show($id = 0)
    {
        if (Auth::check() && $id === 0) {
            $id = Auth::user()->id;
        }
        $user = $this->user->get($id);
        if (!empty($user)) {
            $posts = $user->posts->sortByDesc('created_at');
            if ($posts instanceof Collection) {
                $collection = new Collection();
                foreach ($posts as $item) {
                    $collection->add($item);
                }
                $posts = $collection;
            }
            if (Auth::check()) {
                return View::make('user.show')
                    ->with('title', $user->username)
                    ->with('user', $user)
                    ->with('posts', $posts);
            }

            return Redirect::action('UserController@listUserBlock', [$user->username]);
        }

        return View::make('errors.random')
            ->with('title', 'User does not exist')
            ->with('content', 'The user you tried to view does not exist.');
    }

    /**
     * Lists codeblocks starred by current user.
     *
     * @return mixed
     */
    public function listStarred()
    {
        $user = Auth::user();

        return View::make('user.starred')
            ->with('title', 'Starred codeblock by ' . $user->username)
            ->with('user', $user)
            ->with('posts', $user->stars);
    }

    /**
     * Lists all codeblocks by choosen user.
     *
     * @param PostRepository $post
     * @param int $id id for user who codeblocks to display.
     * @param string $sort
     *
     * @return mixed view object.
     */
    public function listUserBlock(PostRepository $post, $id = 0, $sort = 'date')
    {
        $parameters = Route::getCurrentRoute()->parameters();
        $patterns = Route::getPatterns();

        $matchSort = false;
        if (isset($parameters['username'])) {
            $matchSort = preg_match('/' . $patterns['sort'] . '/', $parameters['username']);
        }

        if (isset($parameters['id']) || isset($parameters['username']) && !$matchSort) {
            $id = (isset($parameters['id'])) ? $parameters['id'] : $parameters['username'];
        } else {
            $id = (Auth::check()) ? Auth::user()->id : $id;
        }

        if ($matchSort) {
            $parameters['sort'] = $parameters['username'];
        }

        if (isset($parameters['sort'])) {
            $sort = $parameters['sort'];
        }

        $user = $this->user->get($id);
        if (is_null($user)) {
            return Redirect::action('MenuController@browse');
        }

        $posts = $post->sort($user->posts, $sort);
        if (!Auth::check() || Auth::check() && Auth::user()->id != $user->id) {
            $posts = CollectionService::filter($posts, 'private', 0);
        }

        $paginator = $this->createPaginator($posts);
        $posts = $paginator['data'];
        $paginator = $paginator['paginator'];

        return View::make('user.list')
            ->with('title', $user->username)
            ->with('user', $user)
            ->with('posts', $posts)
            ->with('paginator', $paginator);
    }

    /**
     * Render view to edit a user.
     * @permission update_users
     *
     * @param RoleRepository $role
     * @param  int $id id for user to edit.
     *
     * @return object     view object.
     */
    public function edit(RoleRepository $role, $id)
    {
        return View::make('user.edit')
            ->with('title', 'Edit User')
            ->with('user', $this->user->get($id))
            ->with('roles', $this->getSelectArray($role->get(), 'id', 'name'));
    }

    /**
     * Updates a user.
     * @permission update_users
     *
     * @param NotificationRepository $notification
     * @param  int $id id for user to update.
     *
     * @return object     redirect object.
     */
    public function update(NotificationRepository $notification, $id)
    {
        if ($this->user->update($this->request->all(), $id)) {
            $newUser = $this->user->get($id);
            if ($newUser->active < 0) {
                $notification->send($newUser->id, NotificationType::BANNED, $newUser);
            }
            if ($newUser->role != $this->request->get('role') && Auth::user()->id != $newUser->id) {
                $notification->send($newUser->id, NotificationType::ROLE, $newUser);
            }

            return Redirect::back()->with('success', 'You have change users rights.');
        }

        return Redirect::back()->withInput()->withErrors($this->user->getErrors());
    }

    /**
     * Delete a user.
     * @permission delete_users
     *
     * @param  int $id id for user to delete.
     *
     * @return object     redirect object.
     */
    public function delete($id)
    {
        if (is_numeric($id) && $id != 1) {
            if ($this->user->delete($id)) {
                return Redirect::to('users')->with('success', 'The user has been deleted.');
            }
        }

        return Redirect::back()->with('error', 'The user could not be deleted.');
    }

    /**
     * Render login view.
     * @return object     view object.
     */
    public function login()
    {
        return View::make('login')->with('title', 'Login / Sign up');
    }

    /**
     * Logging in user.
     * @return object     redirect object.
     */
    public function Usersession()
    {
        if ($this->user->login($this->request->all())) {
            return Redirect::intended('/user')->with('success', 'You have logged in.');
        }

        return Redirect::to('/login')
            ->with('error', "Your username or password is wrong, Don't forget to activate your user.")
            ->withInput(['loginUsername' => $this->request->get('loginUsername')]);
    }

    /**
     * Logging out user.
     * @return object     redirect user.
     */
    public function logout()
    {
        Auth::logout();

        return Redirect::to('/login')->with('success', 'You have logged out.');
    }

    /**
     * Sends a new password to user.
     * @return object     redirect object.
     */
    public function forgotPassword()
    {
        if ($this->user->forgotPassword($this->request->all())) {
            return Redirect::back()->with('success', 'A new password have been sent to you.');
        } else {
            return Redirect::back()->with('error', "Your email don't exists in our database.");
        }
    }

    /**
     * Activates a user.
     *
     * @param  int $id id for user to activate.
     * @param  string $token token for user to activate.
     *
     * @return object     redirect object.
     */
    public function activate($id, $token)
    {
        if ($this->user->activateUser($id, $token)) {
            Session::flash('success', 'Your user has been activated.');
            Auth::login($this->user->get($id));

            return Redirect::to('/user');
        } else {
            Session::flash('error', 'Something went wrong, please try again or contact admin.');

            return Redirect::to('/login');
        }
    }


    /**
     * Log in user with help of social media.
     *
     * @param $social
     * @param Socialite $socialite
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function oauth($social, Socialite $socialite)
    {
        if ($this->request->get('code') || $this->request->get('oauth_token') && $this->request->get('oauth_verifier')) {
            $user = $socialite->driver($social)->user();
            if ($social == 'github') {
                Session::put('github_access_token', $user->token);
            }
            if (Auth::check()) {
                $authedUser = Auth::user();
                $socials = $authedUser->socials;
                $create = true;
                if (count($socials) > 0) {
                    foreach ($socials as $soc) {
                        if ($social == $soc->social) {
                            $create = false;
                            break;
                        }
                    }
                }
                if ($create) {
                    $user = Social::create([
                        "social" => $social,
                        "user_id" => $authedUser->id,
                        "social_id" => $user->getId(),
                    ]);
                    if ($user) {
                        Analytics::track(Analytics::CATEGORY_SOCIAL, Analytics::ACTION_CONNECT, ['social' => $social]);

                        return Redirect::to('/user')
                            ->with('success', 'You have connected ' . $social . ' to your account.');
                    }
                    Analytics::track(Analytics::CATEGORY_ERROR, Analytics::ACTION_CONNECT, ['social' => $social]);

                    return Redirect::to('/user')
                        ->with('error', 'We could not connected ' . $social . ' to your account.');
                } else {
                    Analytics::track(Analytics::CATEGORY_ERROR, Analytics::ACTION_CONNECT, ['social' => $social]);

                    return Redirect::to('/user')
                        ->with('error', 'You have already connected ' . $social . ' to your account.');
                }
            } else {
                try {
                    $socials = Social::all();
                    $id = 0;
                    if (count($socials) > 0) {
                        foreach ($socials as $soc) {
                            if ($social == $soc->social && $user->getId() == $soc->social_id) {
                                $id = $soc->user_id;
                            }
                        }
                    }
                    if ($id == 0) {
                        $id = $this->user->getIdByEmail($user->getEmail());
                        if ($id == 0) {
                            $id = $this->user->getIdByUsername($user->getNickname());
                        }
                        if ($id > 0) {
                            Social::create(["social" => $social, "user_id" => $id, "social_id" => $user->getId()]);
                        }
                    }
                    if ($id > 0) {
                        Auth::loginUsingId($id);
                        Analytics::track(Analytics::CATEGORY_SOCIAL, Analytics::ACTION_LOGIN, [
                            'social' => $social,
                            'username' => Auth::user()->username,
                        ]);

                        return Redirect::to('/user')->with('success', 'You have logged in.');
                    }
                } catch (\Exception $e) {
                }
                Analytics::track(Analytics::CATEGORY_ERROR, Analytics::ACTION_LOGIN, ['social' => $social]);

                return Redirect::to('/login')
                    ->with('error',
                        'We could not log you in with your connected social media, please login with the login form and connect ' . $social . ' with your account.');
            }
        }

        return $socialite->driver($social)->redirect();
    }
}
