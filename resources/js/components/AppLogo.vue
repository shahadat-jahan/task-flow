<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g filter="url(#filter0_dd_2_300)">
            <path
                d="M3 18C3 9.16344 10.1634 2 19 2C27.8366 2 35 9.16344 35 18C35 26.8366 27.8366 34 19 34C10.1634 34 3 26.8366 3 18Z"
                fill="#4F39F6" shape-rendering="crispEdges" />
            <path
                d="M13.6665 19.3333C13.5403 19.3338 13.4167 19.2984 13.3098 19.2313C13.2029 19.1643 13.1173 19.0682 13.0629 18.9544C13.0084 18.8406 12.9874 18.7137 13.0022 18.5884C13.017 18.4632 13.067 18.3446 13.1465 18.2467L19.7465 11.4467C19.796 11.3895 19.8635 11.3509 19.9378 11.3372C20.0122 11.3234 20.089 11.3353 20.1557 11.371C20.2223 11.4067 20.2749 11.4639 20.3047 11.5334C20.3346 11.6029 20.3399 11.6804 20.3198 11.7533L19.0398 15.7667C19.0021 15.8677 18.9894 15.9764 19.0029 16.0833C19.0164 16.1903 19.0556 16.2925 19.1172 16.381C19.1788 16.4695 19.261 16.5417 19.3567 16.5914C19.4524 16.6412 19.5587 16.667 19.6665 16.6667H24.3332C24.4593 16.6662 24.583 16.7016 24.6899 16.7687C24.7967 16.8358 24.8823 16.9318 24.9368 17.0456C24.9913 17.1594 25.0123 17.2863 24.9975 17.4116C24.9827 17.5369 24.9326 17.6554 24.8532 17.7533L18.2532 24.5533C18.2037 24.6105 18.1362 24.6491 18.0618 24.6629C17.9875 24.6766 17.9107 24.6647 17.844 24.629C17.7773 24.5933 17.7248 24.5361 17.6949 24.4666C17.6651 24.3971 17.6598 24.3196 17.6798 24.2467L18.9598 20.2333C18.9976 20.1323 19.0103 20.0237 18.9968 19.9167C18.9833 19.8097 18.9441 19.7076 18.8824 19.6191C18.8208 19.5306 18.7387 19.4583 18.643 19.4086C18.5473 19.3588 18.441 19.333 18.3332 19.3333H13.6665Z"
                stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round" />
        </g>
        <defs>
            <filter id="filter0_dd_2_300" x="0" y="0" width="38" height="38" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                    result="hardAlpha" />
                <feMorphology radius="1" operator="erode" in="SourceAlpha" result="effect1_dropShadow_2_300" />
                <feOffset dy="1" />
                <feGaussianBlur stdDeviation="1" />
                <feComposite in2="hardAlpha" operator="out" />
                <feColorMatrix type="matrix" values="0 0 0 0 0.639216 0 0 0 0 0.701961 0 0 0 0 1 0 0 0 1 0" />
                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2_300" />
                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                    result="hardAlpha" />
                <feOffset dy="1" />
                <feGaussianBlur stdDeviation="1.5" />
                <feComposite in2="hardAlpha" operator="out" />
                <feColorMatrix type="matrix" values="0 0 0 0 0.639216 0 0 0 0 0.701961 0 0 0 0 1 0 0 0 1 0" />
                <feBlend mode="normal" in2="effect1_dropShadow_2_300" result="effect2_dropShadow_2_300" />
                <feBlend mode="normal" in="SourceGraphic" in2="effect2_dropShadow_2_300" result="shape" />
            </filter>
        </defs>
    </svg>

</template>
