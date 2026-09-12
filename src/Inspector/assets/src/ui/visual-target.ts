export interface VisualTarget {
    element: Element;
    framework?: string;
    label?: string;
}

export interface VisualCallbacks {
    onPreview?: (target: VisualTarget) => void;
    onClearPreview?: () => void;
    onSelect?: (target: VisualTarget) => void;
    onClearSelection?: () => void;
}
