<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        // Ambil semua log beserta data user (aktor) yang melakukannya
        $logs = AuditLog::with('user')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($logs);
    }
}
