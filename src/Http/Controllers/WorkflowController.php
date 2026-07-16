<?php

namespace MatthewWegner\BpmnEngine\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MatthewWegner\BpmnEngine\Models\WorkflowDefinition;
use MatthewWegner\BpmnEngine\Services\BpmnParserService;
use Exception;

class WorkflowController extends Controller
{
    protected BpmnParserService $parser;

    public function __construct(BpmnParserService $parser)
    {
        $this->parser = $parser;
    }

    public function storeVersion(Request $request, $definitionId): JsonResponse
    {
        $request->validate([
            'xml' => 'required|string',
        ]);

        $definition = WorkflowDefinition::findOrFail($definitionId);

        // Determine the next version number
        $nextVersionNumber = $definition->versions()->max('version') + 1;

        try {
            // Save the raw layout
            $version = $definition->versions()->create([
                'version'   => $nextVersionNumber,
                'bpmn_xml'  => $request->input('xml'),
                'is_active' => false, // Default to false until reviewed
            ]);

            // Compile the XML into relational tables
            $this->parser->parseAndStore($version, $request->input('xml'));

            return response()->json([
                'success'    => true,
                'message'    => 'Workflow version compiled successfully.',
                'version_id' => $version->id
            ], 201);

        } catch (Exception $e) {
            // If the XML is malformed, clean up the orphaned version record
            if (isset($version)) {
                $version->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to compile workflow: ' . $e->getMessage()
            ], 422);
        }
    }
}