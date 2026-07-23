// vite.config.js
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    build: {
        outDir: 'public',
        emptyOutDir: false,
        lib: {
            entry: 'resources/js/bpmn-engine.js',
            name: 'BpmnDesigner', // The global variable name for the IIFE wrapper
            fileName: () => 'bpmn-engine.js',
            formats: ['iife'] // Compiles a standard, classic script (no export errors!)
        }
    }
});