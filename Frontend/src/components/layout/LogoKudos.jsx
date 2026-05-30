import React from "react";

const LogoKudos = ({ className = "" }) => {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 80 80"
      className={className}
    >
      <defs>
        <linearGradient id="kudos-grad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stopColor="#4FC3F7" />
          <stop offset="100%" stopColor="#2563eb" />
        </linearGradient>
      </defs>

      <g transform="translate(40, 40) scale(0.85)">

        <path d="M -24,14 C -38,25 -25,44 -6,38 C -18,36 -28,26 -18,16 Z" fill="#2563eb" />
        <path d="M -24,14 C -38,25 -25,44 -6,38 C -18,36 -28,26 -18,16 Z" transform="scale(-1, 1)" fill="#2563eb" />

        <polygon points="0,-40 9,-12 38,-12 14,6 23,32 0,15 -23,32 -14,6 -38,-12 -9,-12" fill="#FFA000" />

        <polygon points="0,0 0,-40 -9,-12" fill="#facc15" />
        <polygon points="0,0 0,-40 9,-12" fill="#FFA000" />
        <polygon points="0,0 38,-12 9,-12" fill="#FFE082" />
        <polygon points="0,0 38,-12 14,6" fill="#FF6F00" />
        <polygon points="0,0 23,32 14,6" fill="#FFA000" />
        <polygon points="0,0 23,32 0,15" fill="#facc15" />
        <polygon points="0,0 -23,32 0,15" fill="#FF6F00" />
        <polygon points="0,0 -23,32 -14,6" fill="#facc15" />
        <polygon points="0,0 -38,-12 -14,6" fill="#FFA000" />
        <polygon points="0,0 -38,-12 -9,-12" fill="#facc15" />

        <g fill="url(#kudos-grad)">
          <rect x="-19" y="10" width="7" height="18" rx="1" />
          <rect x="12" y="10" width="7" height="18" rx="1" />
          <polygon points="0,-10 9,5 4,5 4,28 -4,28 -4,5 -9,5" />
        </g>

      </g>
    </svg>
  );
};

export default LogoKudos;