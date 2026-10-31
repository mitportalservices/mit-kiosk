@extends('layouts.app')

@section('content')
@include('admin.partials.navbar')

<main class="pl-64 pt-16 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="p-8 max-w-[95rem] mx-auto">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <h1 class="text-4xl font-extrabold font-manrope tracking-tight text-slate-800 dark:text-slate-100">Kiosk Feedback</h1>
                <p class="text-slate-500 dark:text-slate-400">Ratings and suggestions submitted by users after completed transactions.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.feedbacks.export', request()->query()) }}" 
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 flex items-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">table_view</span>
                    Export CSV / Excel
                </a>
                <div class="px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col items-end">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Total Responses</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ $feedbacks->count() }}</span>
                </div>
            </div>
        </header>

        @include('admin.partials.filter-bar', [
            'routeName' => 'admin.feedbacks',
            'exportRoute' => 'admin.feedbacks.export',
            'totalCount' => $feedbacks->count(),
            'label' => 'Feedbacks'
        ])

        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden transition-colors duration-300">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">#</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Rating</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Suggestions</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Date & Time</th>
                            <th class="text-right px-6 py-4 font-black uppercase tracking-widest text-[10px]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($feedbacks as $index => $feedback)
                            @php
                                $ratingConfig = [
                                    'Very Satisfied'    => ['emoji' => '😄', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-100 dark:border-emerald-800/30'],
                                    'Satisfied'         => ['emoji' => '🙂', 'bg' => 'bg-green-50 dark:bg-green-900/20',     'text' => 'text-green-700 dark:text-green-300',     'border' => 'border-green-100 dark:border-green-800/30'],
                                    'Neutral'           => ['emoji' => '😐', 'bg' => 'bg-slate-100 dark:bg-slate-800',       'text' => 'text-slate-600 dark:text-slate-300',     'border' => 'border-slate-200 dark:border-slate-700'],
                                    'Dissatisfied'      => ['emoji' => '🙁', 'bg' => 'bg-amber-50 dark:bg-amber-900/20',     'text' => 'text-amber-700 dark:text-amber-300',     'border' => 'border-amber-100 dark:border-amber-800/30'],
                                    'Very Dissatisfied' => ['emoji' => '😞', 'bg' => 'bg-rose-50 dark:bg-rose-900/20',       'text' => 'text-rose-700 dark:text-rose-300',       'border' => 'border-rose-100 dark:border-rose-800/30'],
                                ];
                                $cfg = $ratingConfig[$feedback->rating] ?? ['emoji' => '❓', 'bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'border' => 'border-slate-200'];
                            @endphp
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-xs font-bold text-slate-400 dark:text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $cfg['bg'] }} {{ $cfg['text'] }} {{ $cfg['border'] }} whitespace-nowrap">
                                        <span>{{ $cfg['emoji'] }}</span>
                                        <span>{{ $feedback->rating }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300 max-w-sm">
                                    @if($feedback->suggestions)
                                        <span class="font-medium">{{ $feedback->suggestions }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600 italic text-xs font-bold">No suggestions</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $feedback->created_at->format('M d, Y') }}</div>
                                    <div class="text-[10px] opacity-60 uppercase">{{ $feedback->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" onclick="openEditModal({{ $feedback->id }}, '{{ addslashes($feedback->rating) }}', '{{ addslashes($feedback->suggestions ?? '') }}')"
                                            class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-all" title="Edit Feedback">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </button>
                                        <form action="{{ route('admin.feedbacks.destroy', $feedback) }}" method="POST" onsubmit="return confirm('Delete this feedback entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all" title="Delete Feedback">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 dark:text-slate-600 font-bold">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="text-5xl">💬</span>
                                        <span class="font-bold text-slate-400 dark:text-slate-600">No feedback submitted yet.</span>
                                        <span class="text-xs font-medium text-slate-300 dark:text-slate-700">Feedback appears here after users complete a consultation or appointment transaction.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit Feedback Modal -->
<div id="editFeedbackModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in duration-200">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">edit_note</span>
                Edit Feedback
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="editFeedbackForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Rating</label>
                <select id="editRating" name="rating" required
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="Very Satisfied">😄 Very Satisfied</option>
                    <option value="Satisfied">🙂 Satisfied</option>
                    <option value="Neutral">😐 Neutral</option>
                    <option value="Dissatisfied">🙁 Dissatisfied</option>
                    <option value="Very Dissatisfied">😞 Very Dissatisfied</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Suggestions / Comments</label>
                <textarea id="editSuggestions" name="suggestions" rows="4"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-medium text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary"
                    placeholder="User suggestion or comments..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3">
                <button type="button" onclick="closeEditModal()"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-extrabold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-extrabold shadow-lg shadow-primary/20 transition-all">
                    Update Feedback
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, rating, suggestions) {
        const modal = document.getElementById('editFeedbackModal');
        const form = document.getElementById('editFeedbackForm');
        form.action = `/admin/feedbacks/${id}`;
        document.getElementById('editRating').value = rating;
        document.getElementById('editSuggestions').value = suggestions;
        modal.classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editFeedbackModal').classList.add('hidden');
    }
</script>
@endsection
