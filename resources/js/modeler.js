// resources/js/modeler.js
import BpmnModeler from 'bpmn-js/lib/Modeler';

import '@bpmn-io/element-template-chooser/dist/element-template-chooser.css';
import ElementTemplateChooserModule from '@bpmn-io/element-template-chooser';

import {
    BpmnPropertiesPanelModule,
    BpmnPropertiesProviderModule,
    CamundaPlatformPropertiesProviderModule
} from 'bpmn-js-properties-panel';

import {
    ElementTemplatesPropertiesProviderModule, // Camunda 7 Element Templates
    // CloudElementTemplatesPropertiesProviderModule // Camunda 8 Element Templates
} from 'bpmn-js-element-templates';

import camundaModdleDescriptor from 'camunda-bpmn-moddle/resources/camunda.json';

// Custom menu overrides
import { CustomPaletteModule } from './CustomPaletteProvider.js';
import { CustomContextPadModule } from './CustomContextPadProvider.js';

// Expose a globally accessible initialization function
window.initBpmnDesigner = function(initialXml, elementTemplates, saveCallback) {
    const modeler = new BpmnModeler({
        container: '#canvas',
        propertiesPanel: {
            parent: '#properties-panel'
        },
        additionalModules: [
            BpmnPropertiesPanelModule,
            BpmnPropertiesProviderModule,
            CamundaPlatformPropertiesProviderModule,
            ElementTemplatesPropertiesProviderModule,
            ElementTemplateChooserModule,
            // CloudElementTemplatesPropertiesProviderModule,
            CustomPaletteModule/*, // Inject custom palette
            CustomContextPadModule*/ // Inject the context pad filter
        ],
        moddleExtensions: {
            camunda: camundaModdleDescriptor
        },
        // Provide the templates data to the modeler
        elementTemplates: elementTemplates
    });

    function showTemplateErrors(errors) {
        console.error('Failed to parse element templates', errors);

        const errorMessage = `Failed to parse element templates:

            ${ errors.map(error => error.message).join('\n    ') }

        Check the developer tools for details.`;

        document.querySelector('.error-panel pre').textContent = errorMessage;
        document.querySelector('.error-panel').classList.toggle('hidden');
    }

    // Load element templates
    modeler.on('elementTemplates.errors', event => {
        const { errors } = event;

        showTemplateErrors(errors);
    });

    modeler.get('elementTemplatesLoader').setTemplates(elementTemplates);

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