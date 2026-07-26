# Typography

Styled HTML text elements for headings, paragraphs, quotes, lists and inline code.

```twig {"preview":true}
<div class="self-start w-full">
<twig:Typography:H1>Taxing Laughter: The Joke Tax Chronicles</twig:Typography:H1>
<twig:Typography:P class="text-xl text-muted-foreground">
    Once upon a time, in a far-off land, there was a very lazy king who spent all day lounging on his throne. One day, his advisors came to him with a problem: the kingdom was running out of money.
</twig:Typography:P>
<twig:Typography:H2 class="mt-10">The King's Plan</twig:Typography:H2>
<twig:Typography:P>
    The king thought long and hard, and finally came up with <a href="#" class="font-medium text-primary underline underline-offset-4">a brilliant plan</a>: he would tax the jokes in the kingdom.
</twig:Typography:P>
<twig:Typography:Blockquote>
    "After all," he said, "everyone enjoys a good joke, so it's only fair that they should pay for the privilege."
</twig:Typography:Blockquote>
<twig:Typography:H3 class="mt-8">The Joke Tax</twig:Typography:H3>
<twig:Typography:P>
    The king's subjects were not amused. They grumbled and complained, but the king was firm:
</twig:Typography:P>
<twig:Typography:List>
    <li>1st level of puns: 5 gold coins</li>
    <li>2nd level of jokes: 10 gold coins</li>
    <li>3rd level of one-liners : 20 gold coins</li>
</twig:Typography:List>
<twig:Typography:P>
    As a result, people stopped telling jokes, and the kingdom fell into a gloom. But there was one person who refused to let the king's foolishness get him down: a court jester named Jokester.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">Jokester's Revolt</twig:Typography:H3>
<twig:Typography:P>
    Jokester began sneaking into the castle in the middle of the night and leaving jokes all over the place: under the king's pillow, in his soup, even in the royal toilet. The king was furious, but he couldn't seem to stop Jokester.
</twig:Typography:P>
<twig:Typography:P>
    And then, one day, the people of the kingdom discovered that the jokes left by Jokester were so funny that they couldn't help but laugh. And once they started laughing, they couldn't stop.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">The People's Rebellion</twig:Typography:H3>
<twig:Typography:P>
    The people of the kingdom, feeling uplifted by the laughter, started to tell jokes and puns again, and soon the entire kingdom was in on the joke.
</twig:Typography:P>
<div class="my-6 w-full overflow-y-auto">
    <table class="w-full">
        <thead>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <th class="border px-4 py-2 text-left font-bold">King's Treasury</th>
                <th class="border px-4 py-2 text-left font-bold">People's happiness</th>
            </tr>
        </thead>
        <tbody>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Empty</td>
                <td class="border px-4 py-2 text-left">Overflowing</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Modest</td>
                <td class="border px-4 py-2 text-left">Satisfied</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Full</td>
                <td class="border px-4 py-2 text-left">Ecstatic</td>
            </tr>
        </tbody>
    </table>
</div>
<twig:Typography:P>
    The king, seeing how much happier his subjects were, realized the error of his ways and repealed the joke tax. Jokester was declared a hero, and the kingdom lived happily ever after.
</twig:Typography:P>
<twig:Typography:P>
    The moral of the story is: never underestimate the power of a good laugh and always be careful of bad ideas.
</twig:Typography:P>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Typography:H1>The Joke Tax Chronicles</twig:Typography:H1>
<twig:Typography:P>
    Once upon a time, in a far-off land, there was a very lazy king who spent all day lounging on his throne.
</twig:Typography:P>
```

## Examples

### H1

```twig {"preview":true}
<twig:Typography:H1 class="text-center">Taxing Laughter: The Joke Tax Chronicles</twig:Typography:H1>
```

### H2

```twig {"preview":true}
<twig:Typography:H2>The People of the Kingdom</twig:Typography:H2>
```

### H3

```twig {"preview":true}
<twig:Typography:H3>The Joke Tax</twig:Typography:H3>
```

### H4

```twig {"preview":true}
<twig:Typography:H4>People stopped telling jokes</twig:Typography:H4>
```

### Paragraph

```twig {"preview":true}
<twig:Typography:P>
    The king, seeing how much happier his subjects were, realized the error of his ways and repealed the joke tax.
</twig:Typography:P>
```

### Blockquote

```twig {"preview":true}
<twig:Typography:Blockquote>
    "After all," he said, "everyone enjoys a good joke, so it's only fair that they should pay for the privilege."
</twig:Typography:Blockquote>
```

### Table

```twig {"preview":true}
<div class="my-6 w-full overflow-y-auto">
    <table class="w-full">
        <thead>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <th class="border px-4 py-2 text-left font-bold">King's Treasury</th>
                <th class="border px-4 py-2 text-left font-bold">People's happiness</th>
            </tr>
        </thead>
        <tbody>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Empty</td>
                <td class="border px-4 py-2 text-left">Overflowing</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Modest</td>
                <td class="border px-4 py-2 text-left">Satisfied</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-left">Full</td>
                <td class="border px-4 py-2 text-left">Ecstatic</td>
            </tr>
        </tbody>
    </table>
</div>
```

### List

```twig {"preview":true}
<twig:Typography:List>
    <li>1st level of puns: 5 gold coins</li>
    <li>2nd level of jokes: 10 gold coins</li>
    <li>3rd level of one-liners : 20 gold coins</li>
</twig:Typography:List>
```

### Inline Code

```twig {"preview":true}
<twig:Typography:InlineCode>@radix-ui/react-alert-dialog</twig:Typography:InlineCode>
```

### Lead

```twig {"preview":true}
<p class="text-xl text-muted-foreground">
    A modal dialog that interrupts the user with important content and expects a response.
</p>
```

### Large

```twig {"preview":true}
<div class="text-lg font-semibold">Are you absolutely sure?</div>
```

### Small

```twig {"preview":true}
<small class="text-sm leading-none font-medium">Email address</small>
```

### Muted

```twig {"preview":true}
<p class="text-sm text-muted-foreground">Enter your email address.</p>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="self-start w-full" dir="rtl">
<twig:Typography:H1>فرض الضرائب على الضحك: سجلات ضريبة النكتة</twig:Typography:H1>
<twig:Typography:P class="text-xl text-muted-foreground">
    في قديم الزمان، في أرض بعيدة، كان هناك ملك كسول جداً يقضي يومه كله مستلقياً على عرشه. في أحد الأيام، جاءه مستشاروه بمشكلة: المملكة كانت تنفد من المال.
</twig:Typography:P>
<twig:Typography:H2 class="mt-10">خطة الملك</twig:Typography:H2>
<twig:Typography:P>
    فكر الملك طويلاً وبجد، وأخيراً توصل إلى <a href="#" class="font-medium text-primary underline underline-offset-4">خطة عبقرية</a>: سيفرض ضريبة على النكات في المملكة.
</twig:Typography:P>
<twig:Typography:Blockquote class="border-s-2 ps-6 border-l-0 pl-0">
    "في النهاية،" قال، "الجميع يستمتع بنكتة جيدة، لذا من العدل أن يدفعوا مقابل هذا الامتياز."
</twig:Typography:Blockquote>
<twig:Typography:H3 class="mt-8">ضريبة النكتة</twig:Typography:H3>
<twig:Typography:P>
    لم يكن رعايا الملك سعداء. تذمروا واشتكوا، لكن الملك كان حازماً:
</twig:Typography:P>
<twig:Typography:List>
    <li>المستوى الأول من التورية: 5 قطع ذهبية</li>
    <li>المستوى الثاني من النكات: 10 قطع ذهبية</li>
    <li>المستوى الثالث من النكات القصيرة: 20 قطعة ذهبية</li>
</twig:Typography:List>
<twig:Typography:P>
    نتيجة لذلك، توقف الناس عن رواية النكات، وغرقت المملكة في الكآبة. لكن كان هناك شخص واحد رفض أن تحبطه حماقة الملك: مهرج البلاط المسمى المازح.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">ثورة المازح</twig:Typography:H3>
<twig:Typography:P>
    بدأ المازح يتسلل إلى القلعة في منتصف الليل ويترك النكات في كل مكان: تحت وسادة الملك، في حسائه، حتى في المرحاض الملكي. كان الملك غاضباً، لكنه لم يستطع إيقاف المازح.
</twig:Typography:P>
<twig:Typography:P>
    وبعد ذلك، في يوم من الأيام، اكتشف سكان المملكة أن النكات التي تركها المازح كانت مضحكة جداً لدرجة أنهم لم يستطيعوا منع أنفسهم من الضحك. وبمجرد أن بدأوا بالضحك، لم يستطيعوا التوقف.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">ثورة الشعب</twig:Typography:H3>
<twig:Typography:P>
    شعر سكان المملكة بالبهجة من الضحك، وبدأوا في رواية النكات والتورية مرة أخرى، وسرعان ما أصبحت المملكة بأكملها جزءاً من النكتة.
</twig:Typography:P>
<div class="my-6 w-full overflow-y-auto">
    <table class="w-full">
        <thead>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <th class="border px-4 py-2 text-start font-bold">خزينة الملك</th>
                <th class="border px-4 py-2 text-start font-bold">سعادة الشعب</th>
            </tr>
        </thead>
        <tbody>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">فارغة</td>
                <td class="border px-4 py-2 text-start">فائضة</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">متواضعة</td>
                <td class="border px-4 py-2 text-start">راضٍ</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">ممتلئة</td>
                <td class="border px-4 py-2 text-start">منتشٍ</td>
            </tr>
        </tbody>
    </table>
</div>
<twig:Typography:P>
    الملك، عندما رأى مدى سعادة رعاياه، أدرك خطأ طرقه وألغى ضريبة النكتة. أُعلن المازح بطلاً، وعاشت المملكة في سعادة دائمة.
</twig:Typography:P>
<twig:Typography:P>
    مغزى القصة هو: لا تستهن أبداً بقوة الضحك الجيد وكن دائماً حذراً من الأفكار السيئة.
</twig:Typography:P>
</div>
```

```twig {"preview":true}
<div class="self-start w-full" dir="rtl">
<twig:Typography:H1>מיסוי הצחוק: כרוניקות מס הבדיחה</twig:Typography:H1>
<twig:Typography:P class="text-xl text-muted-foreground">
    היה היה פעם, בארץ רחוקה, מלך עצלן מאוד שבילה את כל היום בהתרווחות על כס מלכותו. יום אחד, יועציו באו אליו עם בעיה: הממלכה נגמר לה הכסף.
</twig:Typography:P>
<twig:Typography:H2 class="mt-10">התוכנית של המלך</twig:Typography:H2>
<twig:Typography:P>
    המלך חשב ארוכות וקשות, ולבסוף העלה <a href="#" class="font-medium text-primary underline underline-offset-4">תוכנית גאונית</a>: הוא ימסה את הבדיחות בממלכה.
</twig:Typography:P>
<twig:Typography:Blockquote class="border-s-2 ps-6 border-l-0 pl-0">
    "אחרי הכל," אמר, "כולם נהנים מבדיחה טובה, אז זה רק הוגן שישלמו על הזכות הזו."
</twig:Typography:Blockquote>
<twig:Typography:H3 class="mt-8">מס הבדיחה</twig:Typography:H3>
<twig:Typography:P>
    נתיני המלך לא היו מרוצים. הם התלוננו והתרעמו, אבל המלך היה נחוש:
</twig:Typography:P>
<twig:Typography:List>
    <li>רמה ראשונה של משחקי מילים: 5 מטבעות זהב</li>
    <li>רמה שנייה של בדיחות: 10 מטבעות זהב</li>
    <li>רמה שלישית של חידודים: 20 מטבעות זהב</li>
</twig:Typography:List>
<twig:Typography:P>
    כתוצאה מכך, אנשים הפסיקו לספר בדיחות, והממלכה שקעה בעצב. אבל היה אדם אחד שסירב לתת לטיפשות המלך להפיל אותו: ליצן חצר בשם הבדחן.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">המרד של הבדחן</twig:Typography:H3>
<twig:Typography:P>
    הבדחן התחיל להתגנב לטירה באמצע הלילה ולהשאיר בדיחות בכל מקום: מתחת לכרית המלך, במרק שלו, אפילו בשירותים המלכותיים. המלך היה זועם, אבל הוא לא הצליח לעצור את הבדחן.
</twig:Typography:P>
<twig:Typography:P>
    ואז, יום אחד, תושבי הממלכה גילו שהבדיחות שהבדחן השאיר היו כל כך מצחיקות שהם לא יכלו להתאפק מלצחוק. וברגע שהתחילו לצחוק, הם לא יכלו להפסיק.
</twig:Typography:P>
<twig:Typography:H3 class="mt-8">המרד של העם</twig:Typography:H3>
<twig:Typography:P>
    תושבי הממלכה, שהרגישו מרוממים מהצחוק, התחילו לספר בדיחות ומשחקי מילים שוב, ובקרוב כל הממלכה הייתה חלק מהבדיחה.
</twig:Typography:P>
<div class="my-6 w-full overflow-y-auto">
    <table class="w-full">
        <thead>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <th class="border px-4 py-2 text-start font-bold">אוצר המלך</th>
                <th class="border px-4 py-2 text-start font-bold">אושר העם</th>
            </tr>
        </thead>
        <tbody>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">ריק</td>
                <td class="border px-4 py-2 text-start">גדוש</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">צנוע</td>
                <td class="border px-4 py-2 text-start">מרוצה</td>
            </tr>
            <tr class="m-0 border-t p-0 even:bg-muted">
                <td class="border px-4 py-2 text-start">מלא</td>
                <td class="border px-4 py-2 text-start">אקסטטי</td>
            </tr>
        </tbody>
    </table>
</div>
<twig:Typography:P>
    המלך, כשראה כמה מאושרים נתיניו, הבין את טעותו וביטל את מס הבדיחה. הבדחן הוכרז כגיבור, והממלכה חיה באושר לנצח.
</twig:Typography:P>
<twig:Typography:P>
    המוסר של הסיפור הוא: לעולם אל תזלזל בכוח של צחוק טוב ותמיד היזהר מרעיונות רעים.
</twig:Typography:P>
</div>
```

## API Reference

::: api-reference
