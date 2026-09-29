# Parent app 1.1.2 (build 8)

The parent app opens with child selection and a labelled icon grid for Results, Homework, Timetable, Attendance, Fees, Receipts, Messages, Notices, Events, Documents, Library and Awards. The grid adapts its column count to screen width and text size. Below it, Home contains the child's photo, results summary, attendance and behaviour, actionable notices, and compact school updates. The four navigation destinations are Home, Academics, Updates and More. Existing service routes remain available.

Child selection uses side-by-side photo tabs with names, class labels and a highlighted selected state. More than two children can be reached by horizontal scrolling. The Home tabs sit in the navy header. Child selection is shared between Home, Academics, Attendance and Library. Drill-down links use navigation pushes so Back returns to the originating screen. Restricted results are hidden in both Home and Academics.

The login form scrolls when the keyboard opens. School photo uploads remain staff-controlled; the parent profile displays the saved photo.

## Native splash

The crest is copied unchanged to `android/app/src/main/res/drawable-nodpi/school_crest.png`. Older Android versions use a centered 144 x 178 dp crest. Android 12+ uses a 288 dp transparent canvas with a centered 112 x 138 dp crest, whose corners fit inside the 192 dp circular safe area. Both light and dark launch themes reference `school_splash_icon`.

Do not run splash regeneration without preserving these manually maintained XML layouts and both version-31 theme references. The pubspec includes the same maintenance note.

## Verification

Run `flutter test --no-pub`. Widget coverage includes child selection, restricted marks, four-tab navigation and Back, 320-pixel layouts with 1.4x text, results display, and login with an open keyboard. Optional readable preview output can be enabled with `--dart-define=UX_FONT_DIR=<Flutter SDK>/bin/cache/artifacts/material_fonts`.

Build using `flutter build apk --release --no-pub`. This uses the project's existing signing configuration. Test the splash by fully closing and reopening the installed app on a physical Android phone; a Flutter hot reload does not exercise the native launch screen.

