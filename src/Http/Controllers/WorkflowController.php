<?php

namespace MatthewWegner\BpmnEngine\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MatthewWegner\BpmnEngine\Models\WorkflowDefinition;
use MatthewWegner\BpmnEngine\Services\BpmnParserService;
use Illuminate\Support\Facades\File;
use Exception;

class WorkflowController extends Controller
{
    protected BpmnParserService $parser;

    public function __construct(BpmnParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Display a list of all current workflow definitions.
     */
    public function index()
    {
        $definitions = WorkflowDefinition::with('versions')->get();
        
        return view('bpmn-engine::dashboard', compact('definitions'));
    }

    /**
     * Create a brand new workflow definition and redirect to its designer.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'key'  => 'required|alpha_dash|unique:workflow_definitions,key',
        ]);

        $definition = WorkflowDefinition::create([
            'name' => $request->name,
            'key'  => $request->key,
        ]);

        return redirect()->route('bpmn.design', $definition->id);
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

    /**
     * Display the editor canvas.
     */
    public function design($definitionId)
    {
        $definition = WorkflowDefinition::findOrFail($definitionId);
        
        // Grab the latest saved XML string
        $latestVersion = $definition->versions()->latest('version')->first();
        
        // If there are no saved versions, we pass a strictly validated, clean XML string
        $xml = $latestVersion ? $latestVersion->bpmn_xml : $this->getBlankBlueprintXml();

        // Scan the host application for Element Templates
        $templatesPath = resource_path('bpmn/templates');
        $elementTemplates = [];

        if (File::isDirectory($templatesPath)) {
            foreach (File::files($templatesPath) as $file) {
                if ($file->getExtension() === 'json') {
                    $content = json_decode(File::get($file), true);
                    if (is_array($content)) {
                        // Merge the parsed JSON arrays together
                        $elementTemplates = array_merge($elementTemplates, $content);
                    }
                }
            }
        }

        return view('bpmn-engine::editor', [
            'definition'        => $definition,
            'xml'               => $xml,
            'elementTemplates'  => $elementTemplates,
        ]);
    }

    /**
     * A completely standard, validated, minimal BPMN 2.0 XML blueprint.
     */
    protected function getBlankBlueprintXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
               '<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" ' . // Added 'www.'
               'xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" ' .
               'xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" ' .
               'xmlns:di="http://www.omg.org/spec/DD/20100524/DI" ' . // Added 'di' namespace
               'id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">' .
               '<bpmn:process id="Process_1" isExecutable="true">' .
               '<bpmn:startEvent id="StartEvent_1" />' .
               '</bpmn:process>' .
               '<bpmndi:BPMNDiagram id="BPMNDiagram_1">' .
               '<bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">' .
               '<bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">' .
               '<dc:Bounds x="173" y="102" width="36" height="36" />' .
               '</bpmndi:BPMNShape>' .
               '</bpmndi:BPMNPlane>' .
               '</bpmndi:BPMNDiagram>' .
               '</bpmn:definitions>';
    }
}