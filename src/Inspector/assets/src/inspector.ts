import css from './inspector.css?inline';
import { connectStimulus, registerUXInspector, UXInspector } from './inspector-element';

registerUXInspector({ text: css });

export { connectStimulus, UXInspector };
