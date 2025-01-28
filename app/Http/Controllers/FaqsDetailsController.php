<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaqsDetailsRequest;
use App\Http\Requests\UpdateFaqsDetailsRequest;
use App\Models\FaqsDetails;

class FaqsDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreFaqsDetailsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFaqsDetailsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FaqsDetails  $faqsDetails
     * @return \Illuminate\Http\Response
     */
    public function show(FaqsDetails $faqsDetails)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FaqsDetails  $faqsDetails
     * @return \Illuminate\Http\Response
     */
    public function edit(FaqsDetails $faqsDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFaqsDetailsRequest  $request
     * @param  \App\Models\FaqsDetails  $faqsDetails
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFaqsDetailsRequest $request, FaqsDetails $faqsDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FaqsDetails  $faqsDetails
     * @return \Illuminate\Http\Response
     */
    public function destroy(FaqsDetails $faqsDetails)
    {
        //
    }
}
