<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPMN Workflow Designer</title>
    
    <!-- bpmn-js Canvas & Properties styles -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.2/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.2/dist/assets/bpmn-font/css/bpmn.css" />
    <!-- Properties Panel CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@bpmn-io/properties-panel/dist/assets/properties-panel.css" />

    <style>
        html, body, #canvas {
            height: 100%;
            padding: 0;
            margin: 0;
            background-color: #f8fafc;
        }
        #control-panel {
            position: absolute;
            top: 20px;
            right: 400px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-weight: 600;
        }
        .btn:hover {
            background-color: #4338ca;
        }
        #designer-container {
            display: flex;
            height: 100vh;
        }
        #canvas {
            flex-grow: 1;
            background-color: #f8fafc;
        }
        #properties-panel {
            width: 350px;
            background: #ffffff;
            border-left: 1px solid #e5e7eb;
            overflow-y: auto;
        }
        div[data-group-id="group-CamundaPlatform__AsynchronousContinuations"],
        div[data-group-id="group-CamundaPlatform__Form"],
        div[data-group-id="group-CamundaPlatform__Input"],
        div[data-group-id="group-CamundaPlatform__Output"],
        div[data-group-id="group-CamundaPlatform__HistoryCleanup"],
        div[data-group-id="group-CamundaPlatform__Tasklist"],
        div[data-group-id="group-CamundaPlatform__CandidateStarter"],
        div[data-group-id="group-CamundaPlatform__ExternalTask"],
        div[data-group-id="group-CamundaPlatform__JobExecution"],
        div[data-group-id="group-CamundaPlatform__ExecutionListener"],
        div[data-group-id="group-CamundaPlatform__ExtensionProperties"],
        div[data-group-id="group-CamundaPlatform__FieldInjection"] {
            display: none !important;
        }
    </style>
</head>
<body>

    <div id="control-panel">
        <span style="font-family: sans-serif; font-size: 14px; color: #4b5563;">
            Designing: <strong>{{ $definition->name }}</strong>
        </span>
        <button id="save-button" class="btn">Save & Compile</button>
    </div>

    <!-- Flex container holding both canvas and properties -->
    <div id="designer-container">
        <div id="canvas"></div>
        <div id="properties-panel"></div>
    </div>

    <!-- Load bpmn-js compiled package asset -->
    <script src="{{ asset('vendor/bpmn-engine/bpmn-modeler.js') }}"></script>
    
    <script>
        // Use a safe JSON-decoding assignment to prevent raw quotes or newlines from breaking JS syntax
        const dbXml = {!! $xml ? json_encode($xml) : 'null' !!};
        const elementTemplates = @json($elementTemplates);

        const defaultBlankXml = '<' + '?xml version="1.0" encoding="UTF-8"?>\n' +
        `<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" 
                          xmlns:bpmndi="http://omg.org/spec/BPMN/20100524/DI" 
                          xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" 
                          xmlns:di="http://www.omg.org/spec/DD/20100524/DI" 
                          id="Definitions_1" 
                          targetNamespace="http://bpmn.io/schema/bpmn">
          <bpmn:process id="Process_1" isExecutable="true">
            <bpmn:startEvent id="StartEvent_1" />
          </bpmn:process>
          <bpmndi:BPMNDiagram id="BPMNDiagram_1">
            <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
              <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
                <dc:Bounds x="173" y="102" width="36" height="36" />
              </bpmndi:BPMNShape>
            </bpmndi:BPMNPlane>
          </bpmndi:BPMNDiagram>
        </bpmn:definitions>`;

        const initialXml = dbXml ? dbXml : defaultBlankXml;

        // Initialize our custom bundled modeler
        window.initBpmnDesigner(initialXml, elementTemplates, async (xml) => {
            const response = await fetch('/api/bpmn/workflows/{{ $definition->id }}/versions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ xml: xml })
            });

            const result = await response.json();
            if (response.ok) {
                alert('Workflow saved! Version: ' + result.version_id);
            } else {
                alert('Compilation failed: ' + result.message);
            }
        });
    </script>
</body>
</html>