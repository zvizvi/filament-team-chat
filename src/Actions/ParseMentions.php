<?php

namespace Filament\TeamChat\Actions;

use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Mention;
use Filament\TeamChat\Models\Message;
use Filament\TeamChat\Notifications\NewMentionNotification;

class ParseMentions
{
    /**
     * Parse @mentions from message body and create Mention records.
     * Returns the body with mentions converted to HTML spans.
     */
    public function execute(Message $message, string $bodyHtml): string
    {
        $userModel = config('team-chat.user_model');
        $message->load('user');

        // Parse @channel — notify all channel members
        if (preg_match('/@channel\b/', $message->body)) {
            Mention::create([
                'message_id' => $message->id,
                'type' => 'channel',
            ]);

            $bodyHtml = preg_replace(
                '/@channel\b/',
                '<span class="tc-mention tc-mention--channel">@channel</span>',
                $bodyHtml,
            );

            $this->notifyChannelMembers($message, 'channel');
        }

        // Parse @here — notify all channel members
        if (preg_match('/@here\b/', $message->body)) {
            Mention::create([
                'message_id' => $message->id,
                'type' => 'here',
            ]);

            $bodyHtml = preg_replace(
                '/@here\b/',
                '<span class="tc-mention tc-mention--here">@here</span>',
                $bodyHtml,
            );

            if (! preg_match('/@channel\b/', $message->body)) {
                $this->notifyChannelMembers($message, 'here');
            }
        }

        // Parse @username mentions
        preg_match_all('/@(\w+)/', $message->body, $matches);

        $mentionedNames = collect($matches[1] ?? [])
            ->reject(fn (string $name) => in_array($name, ['channel', 'here']))
            ->unique();

        if ($mentionedNames->isNotEmpty()) {
            $users = $userModel::whereIn('name', $mentionedNames->all())->get();

            foreach ($users as $user) {
                Mention::create([
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                    'type' => 'user',
                ]);

                // Notify mentioned user (skip self)
                if ($user->id !== $message->user_id) {
                    $user->notify(new NewMentionNotification($message, 'user'));
                }

                $bodyHtml = preg_replace(
                    '/@'.preg_quote($user->name, '/').'\b/',
                    '<span class="tc-mention tc-mention--user" data-user-id="'.$user->id.'">@'.e($user->name).'</span>',
                    $bodyHtml,
                );
            }
        }

        return $bodyHtml;
    }

    protected function notifyChannelMembers(Message $message, string $mentionType): void
    {
        if ($message->messageable_type !== Channel::class) {
            return;
        }

        $channel = Channel::find($message->messageable_id);

        if (! $channel) {
            return;
        }

        $channel->members()
            ->where('user_id', '!=', $message->user_id)
            ->get()
            ->each(fn ($member) => $member->notify(
                new NewMentionNotification($message, $mentionType),
            ));
    }
}
