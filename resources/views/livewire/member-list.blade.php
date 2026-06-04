<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.close()">
            <div class="absolute inset-0 bg-black/50" wire:click="close"></div>

            <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-800 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('team-chat::messages.members') }}</h3>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($this->members as $member)
                        <div class="flex w-full items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <button
                                wire:click="showProfile('{{ $member->id }}')"
                                class="flex min-w-0 flex-1 items-center gap-3 text-start"
                            >
                                <div class="relative">
                                    <img src="{{ filament()->getUserAvatarUrl($member) }}" alt="{{ $member->name }}" class="h-9 w-9 rounded-full object-cover">
                                    <span @class([
                                        'absolute -bottom-0.5 -end-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800',
                                        'bg-green-400' => $member->userStatus?->is_online,
                                        'bg-gray-300 dark:bg-gray-600' => ! $member->userStatus?->is_online,
                                    ])></span>
                                </div>
                                <div class="min-w-0 flex-1 text-start">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $member->userStatus?->display_name ?? $member->name }}
                                    </p>
                                    @if($member->userStatus?->getStatusDisplay())
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $member->userStatus->getStatusDisplay() }}
                                        </p>
                                    @endif
                                </div>
                            </button>
                            @if($isOwner && $member->pivot?->role !== 'owner')
                                <button
                                    wire:click="removeMember('{{ $member->id }}')"
                                    wire:confirm="{{ __('team-chat::messages.remove_member_confirm') }}"
                                    class="shrink-0 text-gray-400 hover:text-red-500 transition-colors"
                                    title="{{ __('team-chat::messages.remove_member') }}"
                                >
                                    <x-heroicon-o-user-minus class="h-5 w-5" />
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($isOwner)
                    <div class="border-t border-gray-200 dark:border-gray-700 p-3">
                        <div class="flex items-center gap-2">
                            <select
                                wire:model="addUserId"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ __('team-chat::messages.select_member') }}</option>
                                @foreach($this->addableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <button
                                wire:click="addMember"
                                class="shrink-0 rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700"
                            >
                                {{ __('team-chat::messages.add_member') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
