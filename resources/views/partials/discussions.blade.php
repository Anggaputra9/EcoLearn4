@php
    /** @var \App\Models\Material $material */
    /** @var bool $canPost (default true) */
    $canPost = $canPost ?? true;
    $threads = $material->discussions()->with(['user', 'replies.user'])->latest()->get();
    $isTeacher = auth()->user()->isTeacher() && auth()->id() === $material->teacher_id;
@endphp

<div class="glass p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <x-icon name="chat" class="w-5 h-5 text-emerald-600"/>
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Forum Diskusi</h3>
            <span class="badge badge-slate">{{ $threads->count() }} thread</span>
        </div>
    </div>

    @if($canPost)
        <form method="POST" action="{{ route('discussions.store', $material) }}" class="mb-5 flex gap-2 items-start">
            @csrf
            <img src="{{ auth()->user()->profile_photo_url }}" class="w-9 h-9 rounded-full ring-2 ring-emerald-200 dark:ring-emerald-700/40 object-cover">
            <div class="flex-1">
                <textarea name="body" required rows="2" maxlength="4000" class="input-glass leading-relaxed"
                          placeholder="{{ $isTeacher ? 'Tulis pengumuman atau jawab di thread di bawah…' : 'Ada yang ingin ditanyakan? Tulis di sini.' }}"></textarea>
                <div class="mt-2 flex justify-end">
                    <button class="btn-primary text-sm py-1.5 px-3"><x-icon name="send" class="w-4 h-4"/> Kirim</button>
                </div>
            </div>
        </form>
    @endif

    @if($threads->isEmpty())
        <p class="text-sm text-slate-500">Belum ada diskusi. Jadilah yang pertama bertanya.</p>
    @else
        <div class="space-y-4">
            @foreach($threads as $t)
                <div class="rounded-xl bg-white/50 dark:bg-slate-800/40 border border-white/60 dark:border-white/10 p-4">
                    <div class="flex items-start gap-3">
                        <img src="{{ $t->user->profile_photo_url }}" class="w-9 h-9 rounded-full object-cover ring-2 ring-emerald-200 dark:ring-emerald-700/40">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $t->user->name }}</p>
                                @if($t->user_id === $material->teacher_id) <span class="badge badge-emerald">Guru</span> @endif
                                <span class="text-xs text-slate-500">· {{ $t->created_at->diffForHumans() }}</span>
                                @if($t->is_resolved) <span class="badge badge-violet">Terjawab</span> @endif
                            </div>
                            <p class="mt-1 text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ $t->body }}</p>

                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <button class="btn-ghost text-xs py-1 px-2" @click="$dispatch('open-modal', 'reply-{{ $t->id }}')">
                                    <x-icon name="chat" class="w-3.5 h-3.5"/> Balas ({{ $t->replies->count() }})
                                </button>
                                @if($isTeacher)
                                    <form method="POST" action="{{ route('discussions.resolve', $t) }}" class="inline">
                                        @csrf
                                        <button class="btn-ghost text-xs py-1 px-2">
                                            <x-icon name="check" class="w-3.5 h-3.5"/> {{ $t->is_resolved ? 'Buka kembali' : 'Tandai selesai' }}
                                        </button>
                                    </form>
                                @endif
                                @if($t->user_id === auth()->id() || $isTeacher)
                                    <form method="POST" action="{{ route('discussions.destroy', $t) }}" class="inline" onsubmit="return confirm('Hapus diskusi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost text-xs py-1 px-2 text-rose-600">
                                            <x-icon name="trash" class="w-3.5 h-3.5"/>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($t->replies->count())
                                <div class="mt-4 space-y-3 border-l-2 border-emerald-200 dark:border-emerald-800/40 pl-4">
                                    @foreach($t->replies as $r)
                                        <div class="flex items-start gap-2">
                                            <img src="{{ $r->user->profile_photo_url }}" class="w-7 h-7 rounded-full object-cover ring-1 ring-emerald-200 dark:ring-emerald-700/40">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="font-semibold text-slate-800 dark:text-slate-100 text-sm">{{ $r->user->name }}</p>
                                                    @if($r->user_id === $material->teacher_id) <span class="badge badge-emerald">Guru</span> @endif
                                                    <span class="text-xs text-slate-500">· {{ $r->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap">{{ $r->body }}</p>
                                            </div>
                                            @if($r->user_id === auth()->id() || $isTeacher)
                                                <form method="POST" action="{{ route('discussions.destroy', $r) }}" onsubmit="return confirm('Hapus balasan?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn-ghost p-1 text-rose-600"><x-icon name="trash" class="w-3.5 h-3.5"/></button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($canPost)
                    <x-modal-glass name="reply-{{ $t->id }}" title="Balas Diskusi" max-width="lg">
                        <p class="text-sm text-slate-500 mb-3">Membalas: <span class="font-medium text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($t->body, 80) }}</span></p>
                        <form method="POST" action="{{ route('discussions.store', $material) }}">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $t->id }}">
                            <textarea name="body" required rows="4" class="input-glass" placeholder="Tulis balasan…"></textarea>
                            <div class="mt-3 flex justify-end gap-2">
                                <button type="button" class="btn-secondary" @click="$dispatch('close-modal', 'reply-{{ $t->id }}')">Batal</button>
                                <button class="btn-primary"><x-icon name="send" class="w-4 h-4"/> Kirim</button>
                            </div>
                        </form>
                    </x-modal-glass>
                @endif
            @endforeach
        </div>
    @endif
</div>
