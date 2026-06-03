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

        // Parse @username mentions by matching users' names. Names may contain spaces
        // and non-ASCII characters, so we scan for each user's full name rather than
        // tokenizing on \w. Longest names first so "@First Last" is matched before a
        // shorter "@First" can claim part of it.
        $query = $userModel::query();

        if ($scope = config('team-chat.user_scope')) {
            $query->{$scope}();
        }

        $candidates = $query
            ->orderByRaw('LENGTH(name) DESC')
            ->get();

        $detectionBody = $message->body;

        foreach ($candidates as $user) {
            // Match @Name not immediately followed by another letter/number, so a shorter
            // name does not match inside a longer one (e.g. "@משה" inside "@משה הררי").
            $pattern = '/@'.preg_quote($user->name, '/').'(?![\p{L}\p{N}_])/u';

            if (! preg_match($pattern, $detectionBody)) {
                continue;
            }

            // Blank out the matched span so overlapping shorter names cannot re-match it.
            $detectionBody = preg_replace($pattern, str_repeat(' ', mb_strlen($user->name) + 1), $detectionBody, 1);

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
                $pattern,
                '<span class="tc-mention tc-mention--user" data-user-id="'.$user->id.'">@'.e($user->name).'</span>',
                $bodyHtml,
                1,
            );
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
