<?php

namespace App\Http\Controllers;

use App\Models\Speech;
use App\Adapters\SpeechAdapter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpeechController extends Controller
{
    protected $_speechAdapter;
    protected $_inflectionController;

    public function __construct(SpeechAdapter $adapter, InflectionController $inflectionController) 
    {
        $this->_speechAdapter = $adapter;
        $this->_inflectionController = $inflectionController;
    }

    public function index(Request $request)
    {
        $speeches = Speech::all();
        return view('speech.index', ['speeches' => $speeches]);
    }

    public function create(Request $request)
    {
        return view('speech.create');
    }

    public function edit(Request $request, int $id) 
    {
        $speech = Speech::findOrFail($id);
        return view('speech.edit', ['speech' => $speech]);
    }

    public function store(Request $request)
    {
        $this->validateRequest($request);

        $speech = new Speech;
        $speech->Name = $request->input('name');

        $speech->save();

        return redirect()->route('speech.edit', [ 'id' => $speech->SpeechID ]);
    }

    public function update(Request $request, int $id)
    {
        $this->validateRequest($request, $id);

        $speech = Speech::findOrFail($id);
        $speech->Name = $request->input('name');
        
        $speech->save();

        return redirect()->route('speech.edit', [ 'id' => $speech->SpeechID ]);
    } 

    public function destroy(Request $request, int $id) 
    {
        $speech = Speech::findOrFail($id);
        
        foreach ($speech->inflections as $inflection) {
            $this->_inflectionController->destroy($request, $inflection->InflectionID);
        }

        foreach ($speech->sentenceFragments as $fragment) {
            $fragment->SpeechID = null;
            $fragment->save();
        }

        $speech->delete();

        return redirect()->route('speech.index');
    }

    protected function validateRequest(Request $request, int $id = 0)
    {
        $this->validate($request, [
            'name' => 'required|min:1|max:32|unique:speech,Name'.($id === 0 ? '' : ','.$id.',SpeechID')
        ]);
    } 
}
