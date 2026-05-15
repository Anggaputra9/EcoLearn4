@php $u = auth()->user(); @endphp
<header class="sticky top-0 z-20 px-3 sm:px-6 lg:px-10 pt-3 sm:pt-4">
    <div class="glass flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-2.5">
        <button type="button" class="lg:hidden btn-ghost p-2 shrink-0" @click="sidebarOpen = !sidebarOpen" aria-label="Buka menu">
            <x-icon name="menu-list" class="w-5 h-5"/>
        </button>

        <form action="{{ url('/search') }}" method="GET" class="hidden md:flex items-center flex-1 max-w-md">
            <div class="relative w-full">
                <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"/>
                <input name="q" placeholder="Cari…" class="input-glass pl-9 py-2 text-sm">
            </div>
        </form>

        <div class="ml-auto flex items-center gap-1 sm:gap-2 min-w-0"
             x-data="themeToggle()" x-init="init()">

            <button type="button" @click="cycle()" class="btn-ghost p-2 shrink-0" :title="'Tema: ' + theme" aria-label="Ganti tema">
                <template x-if="theme === 'light'"><x-icon name="sun" class="w-5 h-5"/></template>
                <template x-if="theme === 'dark'"><x-icon name="moon" class="w-5 h-5"/></template>
                <template x-if="theme === 'system'"><x-icon name="monitor" class="w-5 h-5"/></template>
            </button>

            <span class="hidden sm:inline-flex badge badge-emerald">
                @if($u->isAdmin())   Administrator
                @elseif($u->isTeacher()) Guru
                @elseif($u->isStudent()) Siswa
                @endif
            </span>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-1.5 sm:px-2 py-1.5 rounded-xl hover:bg-white/60 dark:hover:bg-white/10 transition min-w-0">
                <img src="{{ $u->profile_photo_url }}" class="w-8 h-8 rounded-full ring-2 ring-emerald-200 dark:ring-emerald-700/40 object-cover shrink-0" alt="">
                <span class="hidden sm:inline text-sm font-medium text-slate-700 dark:text-slate-200 truncate max-w-[10rem]">{{ $u->name }}</span>
            </a>
        </div>
    </div>
</header>


<script>
    function themeToggle() {
        return {
            theme: localStorage.getItem('theme') || @json($u->theme ?? 'light'),
            init() { window.__themeApply?.(this.theme); },
            cycle() {
                this.theme = this.theme === 'light' ? 'dark' : (this.theme === 'dark' ? 'system' : 'light');
                window.__themeApply?.(this.theme);
                fetch('{{ route("profile.theme") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                               'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ theme: this.theme })
                }).catch(()=>{});
            },
        }
    }
</script>
