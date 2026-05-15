<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-slate-800">Hasil Siswa</h2>
        <p class="text-sm text-slate-500">{{ $material->title }}</p>
    </x-slot>

    <div class="glass overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/40 text-xs uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-5 py-3 text-left">Siswa</th>
                        <th class="px-5 py-3 text-left">Soal</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Skor</th>
                        <th class="px-5 py-3 text-left">Feedback</th>
                        <th class="px-5 py-3 text-left">Dikoreksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/40">
                    @forelse($submissions as $s)
                        <tr class="hover:bg-white/40 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ $s->user->name }}</td>
                            <td class="px-5 py-3 text-slate-600 max-w-xs truncate">{{ $s->question->prompt_text }}</td>
                            <td class="px-5 py-3">
                                @if($s->status === 'graded')
                                    <span class="badge badge-emerald">Selesai</span>
                                @elseif($s->status === 'pending')
                                    <span class="badge badge-amber">Pending</span>
                                @else
                                    <span class="badge badge-rose">Gagal</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-bold text-slate-800">{{ $s->score ?? '-' }}</td>
                            <td class="px-5 py-3 text-slate-600 max-w-md"><p class="line-clamp-2">{{ $s->feedback ?? '-' }}</p></td>
                            <td class="px-5 py-3 text-slate-500">{{ optional($s->graded_at)->diffForHumans() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada jawaban yang masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $submissions->links() }}</div>
    </div>
</x-app-layout>
