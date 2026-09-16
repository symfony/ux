import type { ComponentData, RenderContext } from '../types';
import type { StimulusData } from '../stimulus/plugin';
import type { LiveData } from '../live-component/plugin';
import type { TurboData } from '../turbo/plugin';
import { renderStimulus } from '../stimulus/render';
import { renderLive } from '../live-component/render';
import { renderTurbo } from '../turbo/render';

export function renderComponent(data: ComponentData, context: RenderContext): Node | null {
    switch (data.type) {
        case 'stimulus':
            return renderStimulus(data.data as StimulusData, context);
        case 'livecomponent':
            return renderLive(data.data as LiveData, context);
        case 'turbo':
            return renderTurbo(data.data as TurboData, context);
        default:
            return null;
    }
}
