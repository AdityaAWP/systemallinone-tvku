<?php

namespace App\Http\Controllers;

use App\Models\LetterAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterAttachmentController extends Controller
{
    public function download(LetterAttachment $attachment): StreamedResponse
    {
        return Storage::disk('public')->download($attachment->path, $attachment->filename);
    }
    
    public function delete(LetterAttachment $attachment)
    {
        // Delete the file from storage
        Storage::disk('public')->delete($attachment->path);
        
        // Delete the record
        $attachment->delete();
        
        return redirect()->back()->with('success', 'File deleted successfully');
    }
}