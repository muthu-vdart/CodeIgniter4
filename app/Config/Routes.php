<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('api', ['namespace' => 'App\\Controllers', 'filter' => 'apicors'], static function ($routes) {
    $routes->post('login', 'AuthController::login');
    $routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
    $routes->post('refresh-token', 'AuthController::refreshToken');
    $routes->post('reset-password', 'AuthController::resetPassword', ['filter' => 'auth']);

    $routes->group('', ['filter' => 'auth'], static function ($routes) {
        $routes->get('notifications', 'NotificationController::index');
        $routes->put('notifications/read', 'NotificationController::markRead');

        $routes->get('tasks', 'TaskController::index');
        $routes->get('tasks/(:num)', 'TaskController::show/$1');
        $routes->post('tasks', 'TaskController::create', ['filter' => 'permission:create_task']);
        $routes->put('tasks/(:num)', 'TaskController::update/$1', ['filter' => 'permission:update_task']);
        $routes->delete('tasks/(:num)', 'TaskController::delete/$1', ['filter' => 'permission:update_task']);

        $routes->post('tasks/(:num)/assign', 'TaskController::assign/$1', ['filter' => 'permission:assign_task']);
        $routes->put('tasks/(:num)/reassign', 'TaskController::reassign/$1', ['filter' => 'permission:assign_task']);
        $routes->put('tasks/(:num)/status', 'TaskController::updateStatus/$1', ['filter' => 'permission:update_task']);

        $routes->get('tasks/(:num)/comments', 'CommentController::index/$1');
        $routes->post('tasks/(:num)/comments', 'CommentController::create/$1', ['filter' => 'permission:comment_task']);

        $routes->post('tasks/(:num)/attachments', 'TaskController::uploadAttachment/$1', ['filter' => 'permission:update_task']);

        $routes->get('departments', 'DepartmentController::index');
        $routes->post('departments', 'DepartmentController::create', ['filter' => 'permission:manage_departments']);
        $routes->put('departments/(:num)', 'DepartmentController::update/$1', ['filter' => 'permission:manage_departments']);
        $routes->delete('departments/(:num)', 'DepartmentController::delete/$1', ['filter' => 'permission:manage_departments']);

        $routes->get('users', 'UserController::index', ['filter' => 'permission:manage_users']);
        $routes->get('users/(:num)', 'UserController::show/$1', ['filter' => 'permission:manage_users']);
        $routes->post('users', 'UserController::create', ['filter' => 'permission:manage_users']);
        $routes->put('users/(:num)', 'UserController::update/$1', ['filter' => 'permission:manage_users']);
        $routes->delete('users/(:num)', 'UserController::delete/$1', ['filter' => 'permission:manage_users']);

        $routes->get('roles', 'RoleController::index', ['filter' => 'permission:manage_users']);

        $routes->get('admin/notes', 'AdminNoteController::index', ['filter' => 'permission:manage_users']);
        $routes->post('admin/notes', 'AdminNoteController::create', ['filter' => 'permission:manage_users']);
        $routes->put('admin/notes/(:num)', 'AdminNoteController::update/$1', ['filter' => 'permission:manage_users']);
        $routes->delete('admin/notes/(:num)', 'AdminNoteController::delete/$1', ['filter' => 'permission:manage_users']);

        $routes->get('admin/drive', 'AdminDriveController::index', ['filter' => 'permission:manage_users']);
        $routes->post('admin/drive', 'AdminDriveController::upload', ['filter' => 'permission:manage_users']);
    });
});
