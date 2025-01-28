<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InteractionController extends Controller
{
    use Response;

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $interactions = Interaction::query()
//                ->select('category', DB::raw('count(*) as total'))
//                ->groupBy('category')

                ->get()
                ->groupBy(['page', fn($interaction) => $interaction->category->value])
            ;
            return $this->success('Interaction stats.', 200, [
                'interactions' => $interactions
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to retrieve stats.', 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Interaction  $interaction
     * @return \Illuminate\Http\Response
     */
    public function show(Interaction $interaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Interaction  $interaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Interaction $interaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Interaction  $interaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Interaction $interaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Interaction  $interaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Interaction $interaction)
    {
        //
    }
}
