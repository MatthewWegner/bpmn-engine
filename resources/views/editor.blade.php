<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPMN Workflow Designer</title>
    
    <!-- bpmn-js Canvas & Properties styles -->
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.2/dist/assets/diagram-js.css" />
    <link rel="stylesheet" href="https://unpkg.com/bpmn-js@17.0.2/dist/assets/bpmn-font/css/bpmn.css" />

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
            right: 20px;
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
    </style>
</head>
<body>

    <div id="control-panel">
        <span style="font-family: sans-serif; font-size: 14px; color: #4b5563;">
            Designing: <strong>{{ $definition->name }}</strong>
        </span>
        <button id="save-button" class="btn">Save & Compile</button>
    </div>

    <div id="canvas"></div>

    <!-- bpmn-js Modeler library -->
    <script src="https://unpkg.com/bpmn-js@17.0.2/dist/bpmn-modeler.development.js"></script>
    
    <script>
        // Laravel handles quote-wrapping and multi-line escaping perfectly using json
        const initialXml = @json($xml);

        // Initialize the modeler canvas
        const modeler = new BpmnJS({
            container: '#canvas'
        });

        // Load the initial layout safely
        async function openDiagram(xml) {
            try {
                await modeler.importXML(xml);
                modeler.get('canvas').zoom('fit-viewport');
            } catch (err) {
                console.error('Error rendering diagram:', err);
                alert('An error occurred while displaying the BPMN canvas. Check browser console.');
            }
        }

        openDiagram(initialXml);

        // Capture XML and Post back to your package's REST Controller
        document.getElementById('save-button').addEventListener('click', async () => {
            try {
                const { xml } = await modeler.saveXML({ format: true });
                
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
                    alert('Workflow saved and compiled successfully! Version: ' + result.version_id);
                } else {
                    alert('Compilation failed: ' + result.message);
                }
            } catch (err) {
                console.error('Error saving diagram', err);
                alert('An error occurred while exporting the XML.');
            }
        });
    </script>
</body>
</html>