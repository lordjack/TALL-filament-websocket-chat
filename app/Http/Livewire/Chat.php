<?php

namespace App\Http\Livewire;

use App\Events\NewChatMessage;
use Livewire\Component;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;

class Chat extends Component
{

    public $conversations;
    public $conversation;
    public $users;
    public $myUserId;
    public $messages = [];
    public $content;
    public $searchWord = '';
    public function getListeners()
    {
        return [
            'echo:chat,NewChatMessage' => 'getNewMessage'
        ];
    }

    public function openConversation($conversationId)
    {
        $this->conversation = Conversation::find($conversationId);
        $this->getMessages();
    }
    public function mount()
    {
        $this->conversations = auth()->user()->conversations();
        $this->getUser();
        $this->myUserId = auth()->user()->id;
    }

    public function send()
    {
        if (empty(trim($this->content))) {
            return;
        }
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->myUserId,
            'content' => $this->content,
        ]);

        broadcast(new NewChatMessage($message->id, $this->conversation->id))->toOthers();
        $this->messages->push($message);
        $this->reset('content');
    }

    public function getMessages()
    {
        $this->messages = Message::where('conversation_id', $this->conversation->id)->orderBy('id', 'asc')->get();
    }
    public function getNewMessage($event)
    {
        $message = Message::find($event['messageId']);
        //        $conversation = Conversation::find($event['conversationId']);

        if ($message['user_id'] != auth()->user()->id) {
            $this->messages->push($message);
        }
    }
    public function emphasize($string, $word)
    {
        $index1 = stripos($string, $word);
        $index2 = strlen($word);
        return substr($string, 0, $index1) . '<b>' . substr($string, $index1, $index2) . '</b>' . substr($string, $index1 + $index2);
    }
    public function getUser()
    {
        $interlocutorIds = $this->conversations->map(function ($conversation) {
            return $conversation->interlocutor(true);
        })->all();
        $this->users = User::whereNotIn('id', $interlocutorIds)
            ->get();
    }
    public function render()
    {
        return view('livewire.chat');
    }
}