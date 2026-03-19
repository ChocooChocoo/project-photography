<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InquiryController extends Controller
{
    /**
     * @var ChatbotService
     */
    protected $chatbotService;

    /**
     * Constructor
     *
     * @param ChatbotService $chatbotService
     */
    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Display the inquiries page with chatbot integration
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Pass config ID to view for form requests
        $config = \App\Models\Chatbot\ChatbotConfigModel::byOwner(Auth::id())->first();
        $configId = $config ? $config->id : null;
        
        return view('owner.view-inquiries', compact('configId'));
    }
}