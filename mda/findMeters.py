import pandas as pd
import numpy as np
import datetime
import re
import argparse

pd.set_option('display.max_row',10)
pd.set_option('display.max_colwidth',225)
def find_meters(input_file):
    trendmeters = []
    df = pd.read_csv(input_file,
                     #nrows=400000,
                     converters={'Object Value': drop_units})
    df.sort_values(by=['Object Name', 'Date / Time'], inplace=True)
    for trend in (df['Object Name'].unique()):
        z = (df[df['Object Name'] == trend]['Object Value'])
        summary = check_summarizable(np.array(z))
        trendmeters.append((trend,summary))
    return trendmeters


def drop_units(value):
    """Remove the units from a string, e.g. '309.2 kWh' -> 309.2"""
    pattern = re.compile(r"\A(\d*\.?\d+) ?[a-zA-Z]*\Z")
    match = pattern.match(value)
    if match is None:
        # value was not of the expected format
        return np.nan
    else:
        return float(match.group(1))


def check_summarizable(series):
    boolseriesrising = ((series[1:] - series[:-1]) > 0)
    boolseriesbroken = ((series[1:] - series[:-1]) == 0)
    boolseriesfalling = ((series[1:] - series[:-1]) < 0)
    rising = boolseriesrising.mean()
    broken = boolseriesbroken.mean()
    falling = boolseriesfalling.mean()
    #print('broken', broken)
    #print('rising', rising)
    #print('falling', falling)
    #print('total', broken + rising + falling)

    if broken > .9:
        return False
    elif rising / (rising + falling) > .9:
        return True
    else:
        return False


def only_meters(array,output_file):
    with open(output_file, 'w') as f:
        for entry in array:
            if entry[1]:
                print(entry[0])
                f.write(str(entry[0]) + '\n')




if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='script to find meters in Metasys data files')
    parser.add_argument('-i', dest='input_file',  help='name of input file')
    parser.add_argument('-o', dest='output_file', help='name of output file')
    args = parser.parse_args()

    print(datetime.datetime.now())
    q = find_meters(args.input_file)
    only_meters(q,args.output_file)
    print(datetime.datetime.now())
