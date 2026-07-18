// resources/js/modeler.js
import BpmnModeler from 'bpmn-js/lib/Modeler';
import {
    BpmnPropertiesPanelModule,
    BpmnPropertiesProviderModule,
    CamundaPlatformPropertiesProviderModule
} from 'bpmn-js-properties-panel';
import camundaModdleDescriptor from 'camunda-bpmn-moddle/resources/camunda.json';

// Custom menu overrides
import { CustomPaletteModule } from './CustomPaletteProvider.js';
import { CustomContextPadModule } from './CustomContextPadProvider.js';

// Expose a globally accessible initialization function
window.initBpmnDesigner = function(initialXml, saveCallback) {
    const modeler = new BpmnModeler({
        container: '#canvas',
        propertiesPanel: {
            parent: '#properties-panel'
        },
        additionalModules: [
            BpmnPropertiesPanelModule,
            BpmnPropertiesProviderModule,
            CamundaPlatformPropertiesProviderModule,
            CustomPaletteModule, // Inject custom palette
            CustomContextPadModule // Inject the context pad filter
        ],
        moddleExtensions: {
            camunda: camundaModdleDescriptor
        }
    });

    async function render() {
        try {
            await modeler.importXML(initialXml);
            modeler.get('canvas').zoom('fit-viewport');
        } catch (err) {
            console.error('Error rendering diagram:', err);
            alert('An error occurred while displaying the BPMN canvas.');
        }
    }

    render();

    // Attach the save event to the button
    document.getElementById('save-button').addEventListener('click', async () => {
        try {
            const { xml } = await modeler.saveXML({ format: true });
            saveCallback(xml);
        } catch (err) {
            console.error('Error saving diagram:', err);
            // alert('An error occurred while exporting the XML.');
        }
    });
};