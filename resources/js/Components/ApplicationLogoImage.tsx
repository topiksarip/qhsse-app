import { ImgHTMLAttributes } from 'react';

/**
 * Renders the SAMUDERA company logo as a raster asset (preserves the exact
 * source aspect ratio — never force width/height independently).
 * The logo has a transparent background, so a subtle rounded plaque keeps it
 * legible in both light and dark themes. Pass `className` for sizing only via
 * width (height auto) to guarantee the ratio stays intact.
 */
export default function ApplicationLogoImage({ className = '', ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/img/samudera-logo.png"
            alt="SAMUDERA"
            width={1247}
            height={200}
            {...props}
            className={`block h-auto w-auto select-none ${className}`}
        />
    );
}
