<?php
namespace App\Services;
use App\Models\User;

class UserService {
    protected $user;
    public function __construct(User $user) {
        $this->user = $user;
    }

    public function getUsersByPhone($phone) {
        return $this->user->getUsersByPhone($phone);
    }

    public function getUsersByPhoneArray($contact_list) {
        $users = [];
        foreach($contact_list as $contact) {
            $row = [];
            $user = $this->user->getUserByPhone($contact['phone']);
            if($user) {
                $row = $user->toArray();
                $row['exists'] = 1;
            }   else {
                $row['id'] = NULL;
                $row['name'] = $contact['name'];
                $row['email'] = NULL;
                $row['phone_number'] = $contact['phone'];
                $row['image'] = NULL;
                $row['exists'] = 0;
            }
            $users[] = $row;
        }
        return $users;
    }

    public function getAllRegisteredUsers() {
        return $this->user->getAllAppUsers();
    }

    public function updateNotificationSettings($data) {
        return $this->user->updateNotificationSettings($data);
    }

    public function getUserRecentRequests() {
        return $this->user->getUserRecentRequests();
    }
}
?>