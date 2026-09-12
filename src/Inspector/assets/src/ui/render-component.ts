import type { ComponentData, RenderContext } from '../types';
import type { StimulusData } from '../stimulus/plugin';
import type { LiveData } from '../live-component/plugin';
import type { TurboData } from '../turbo/plugin';
import { renderStimulus } from '../stimulus/render';
import { renderLive } from '../live-component/render';
import { renderTurbo } from '../turbo/render';

export function renderComponent(name: string, data: ComponentData, context: RenderContext): Node | null {
    switch (name) {
        case 'stimulus':
            return renderStimulus(data as ComponentData<StimulusData>, context);
        case 'livecomponent':
            return renderLive(data as ComponentData<LiveData>, context);
        case 'turbo':
            return renderTurbo(data as ComponentData<TurboData>, context);
        default:
            return null;
    }
}
