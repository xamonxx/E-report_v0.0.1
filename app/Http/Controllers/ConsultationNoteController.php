<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultationNoteRequest;
use App\Models\Consultation;
use App\Models\ConsultationNote;

class ConsultationNoteController extends Controller
{
    public function store(ConsultationNoteRequest $request, Consultation $consultation)
    {
        $this->authorize('addNote', $consultation);

        $user = auth()->user();

        $consultation->timelineNotes()->create([
            'user_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        return back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function destroy(Consultation $consultation, ConsultationNote $note)
    {
        // Validasi: note harus berkaitan dengan consultation ini
        if ($note->consultation_id !== $consultation->id) {
            abort(404);
        }

        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Catatan berhasil dihapus.');
    }
}
