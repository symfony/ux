import { loadControllers, startApplication } from "./runtime.js";
import { eagerControllers, isApplicationDebug, lazyControllers } from "./controllers.js";
const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);
export { loadControllers, startStimulusApp };
