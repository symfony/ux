import { UXIconData } from "./index.js";
import { ReactElement, SVGProps } from "react";
type UXIconProps = Omit<SVGProps<SVGSVGElement>, 'dangerouslySetInnerHTML' | 'children'> & {
  icon: UXIconData;
};
declare function UXIcon({
  icon,
  ...props
}: UXIconProps): ReactElement;
export { UXIcon, UXIconProps };