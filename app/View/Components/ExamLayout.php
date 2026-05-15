<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout khusus ruang ujian: TANPA sidebar / header / topbar.
 * Pakai dengan: <x-exam-layout> ... </x-exam-layout>
 */
class ExamLayout extends Component
{
    public function render(): View
    {
        return view('layouts.exam');
    }
}
