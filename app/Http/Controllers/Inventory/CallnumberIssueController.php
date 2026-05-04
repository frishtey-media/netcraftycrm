<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CallnumberIssue;

class CallnumberIssueController extends Controller
{
    public function index(Request $request)
    {
        $query = CallnumberIssue::query();

        if ($request->search) {
            $query->where('callnumber', 'like', '%' . $request->search . '%')
                ->orWhere('staff_name', 'like', '%' . $request->search . '%')
                ->orWhere('client_name', 'like', '%' . $request->search . '%');
        }

        $issues = $query->latest()->get();

        return view('inventory.callnumber_issue.index', compact('issues'));
    }

    public function create()
    {
        return view('inventory.callnumber_issue.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'callnumber' => 'required',
            'staff_name' => 'required',
            'client_name' => 'required',
        ]);

        CallnumberIssue::create([
            'callnumber' => $request->callnumber,
            'staff_name' => $request->staff_name,
            'client_name' => $request->client_name,
            'issued_at' => now(),
            'remarks' => $request->remarks,
        ]);

        return redirect()->route('callnumber-issues.index')
            ->with('success', 'Number Issued Successfully');
    }

    public function destroy($id)
    {
        CallnumberIssue::findOrFail($id)->delete();

        return redirect()->route('callnumber-issues.index')
            ->with('success', 'Deleted Successfully');
    }
    public function edit($id)
    {
        $issue = CallnumberIssue::findOrFail($id);
        return view('inventory.callnumber_issue.edit', compact('issue'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'callnumber' => 'required',
            'staff_name' => 'required',
            'client_name' => 'required',
        ]);

        $issue = CallnumberIssue::findOrFail($id);

        $issue->update([
            'callnumber' => $request->callnumber,
            'staff_name' => $request->staff_name,
            'client_name' => $request->client_name,
            'remarks' => $request->remarks,
        ]);

        return redirect()->route('callnumber-issues.index')
            ->with('success', 'Updated Successfully');
    }
}
