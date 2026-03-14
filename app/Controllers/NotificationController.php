<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class NotificationController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $notifications = (new NotificationModel())
            ->where('user_id', (int) $this->user()['id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return api_success('Notifications fetched successfully', $notifications);
    }

    public function markRead()
    {
        helper('api_response');

        $payload = $this->payload();
        $ids = array_map('intval', (array) ($payload['notification_ids'] ?? []));

        $model = new NotificationModel();
        if ($ids) {
            $model->where('user_id', (int) $this->user()['id'])->whereIn('id', $ids)->set('is_read', 1)->update();
        } else {
            $model->where('user_id', (int) $this->user()['id'])->set('is_read', 1)->update();
        }

        return api_success('Notifications marked as read');
    }
}
